{{-- resources/views/livewire/pages/flight/flight-payment-gateway.blade.php --}}
@component('layouts.app', ['title' => 'Secure Payment'])

@php
    $currency = $flight['currency'] ?? 'NGN';
    $sym      = match($currency) { 'NGN' => '₦', 'USD' => '$', 'GBP' => '£', 'EUR' => '€', default => $currency.' ' };
    $fmt      = fn($v) => $sym . number_format((float)$v, 2);
    $total    = ($flight['price'] ?? 0) + ($extrasTotal ?? 0);
    $cabinLabel = \App\Support\FlightDisplay::cabin($flight ?? []);
    $segments = $flight['segments'] ?? [];
    $multiLegs = $flight['multiLegs'] ?? [];
    $isMulti = count($multiLegs) > 0;
    $firstSeg = $segments[0] ?? [];
    $lastSeg  = !empty($segments) ? $segments[count($segments)-1] : [];
    $routeLines = [];
    if ($isMulti) {
        foreach ($multiLegs as $li => $leg) {
            $routeLines[] = [
                'label' => 'Leg ' . ($li + 1),
                'route' => ($leg['from'] ?? ($leg['segments'][0]['from'] ?? '')) . ' → ' . ($leg['to'] ?? ''),
                'date'  => $leg['departDateLabel'] ?? '',
            ];
        }
    } else {
        $routeLines[] = [
            'label' => 'Flight',
            'route' => ($firstSeg['from'] ?? '') . ' → ' . ($lastSeg['to'] ?? ''),
            'date'  => $flight['departDateLabel'] ?? '',
        ];
    }

    // This page is reached two ways: TravelNext WebFare fares (pay first,
    // then book — the only fare type routed here) and every SkyLink fare
    // (SkyLink has no hold concept at all, so it always pays first
    // regardless of fare type). "Low Cost Carrier (LCC)" is TravelNext's own
    // fare-type jargon — accurate for the first case, meaningless for the
    // second — so copy here describes the actual mechanic instead of a
    // fare-type label that doesn't apply to both suppliers.
    $isSkylink = ($flight['source'] ?? null) === 'skylink';
@endphp

<link rel="stylesheet" href="{{ asset('css/travelwheel-ui.css') }}">
<style>
    .fpg-strip{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:16px 18px;background:var(--tw-ui-surface-soft);border:1px solid var(--tw-ui-line);border-radius:var(--tw-ui-radius-md);margin-bottom:20px}
    .fpg-route{display:flex;align-items:center;gap:8px;color:var(--tw-ui-ink);font-size:17px;font-weight:750;font-variant-numeric:tabular-nums}
    .fpg-route svg{color:var(--tw-ui-subtle);flex-shrink:0}
    .fpg-route-meta{margin-top:4px;color:var(--tw-ui-muted);font-size:12.5px}
    .fpg-leg-tag{font-size:11px;font-weight:700;color:var(--tw-ui-brand);background:#eef0ff;padding:2px 9px;border-radius:999px}
    .fpg-amount{flex-shrink:0;padding:9px 16px;background:#fff;border:1px solid var(--tw-ui-line-strong);border-radius:var(--tw-ui-radius-sm);color:var(--tw-ui-ink);font-size:15px;font-weight:750;font-variant-numeric:tabular-nums}
    .fpg-pay{text-align:center;padding:8px 0 4px}
    .fpg-pay-icon{width:46px;height:46px;margin:0 auto 14px;border-radius:50%;background:#eef0ff;color:var(--tw-ui-brand);display:flex;align-items:center;justify-content:center}
    .fpg-pay-title{color:var(--tw-ui-ink);font-size:15.5px;font-weight:750;margin-bottom:6px}
    .fpg-pay-copy{max-width:380px;margin:0 auto 22px;color:var(--tw-ui-muted);font-size:13px;line-height:1.55}
    .fpg-pay-btn{width:100%;flex-direction:column;gap:2px;height:auto;padding:14px}
    .fpg-pay-btn small{display:block;font-size:11px;font-weight:600;opacity:.85}
    .fpg-trust{display:flex;align-items:center;justify-content:center;gap:6px;margin-top:16px;color:var(--tw-ui-subtle);font-size:11.5px}
    .fpg-badges{display:flex;align-items:center;justify-content:center;gap:10px;margin-top:14px}
    .fpg-back{display:inline-flex;align-items:center;gap:6px;color:var(--tw-ui-muted);font-size:13px;font-weight:600;text-decoration:none;margin-bottom:6px}
    .fpg-back:hover{color:var(--tw-ui-ink)}
    .fpg-summary-note{margin-top:14px;color:var(--tw-ui-subtle);font-size:11px;line-height:1.5;text-align:center}
    @media(max-width:800px){.fpg-route{font-size:15px}}
</style>

<x-ui.page-shell eyebrow="Secure checkout" title="Complete your payment" copy="Review your trip below, then pay securely to confirm your booking.">

    <a href="{{ route('flights.booking') }}" class="fpg-back">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Back to booking
    </a>

    @if(session('error') || $errors->has('error'))
        <x-ui.alert variant="error" role="alert">
            {{ session('error') ?? $errors->first('error') ?? 'Payment error occurred' }}
        </x-ui.alert>
    @endif

    <x-ui.card title="Your flight">
        <div class="fpg-strip">
            <div>
                @if($isMulti)
                    <div style="display:flex;flex-direction:column;gap:6px;">
                        @foreach($routeLines as $line)
                            <div class="fpg-route" style="font-size:15px;">
                                <span>{{ $line['route'] }}</span>
                                <span class="fpg-leg-tag">{{ $line['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="fpg-route-meta">{{ $flight['airline'] ?? '' }} · {{ count($routeLines) }} legs · {{ $cabinLabel }}</div>
                @else
                    <div class="fpg-route">
                        {{ $firstSeg['from'] ?? '' }}
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        {{ $lastSeg['to'] ?? '' }}
                    </div>
                    <div class="fpg-route-meta">
                        {{ $flight['airline'] ?? '' }}
                        @if(!empty($flight['departDateLabel'])) · {{ $flight['departDateLabel'] }} @endif
                        · {{ $cabinLabel }}
                    </div>
                @endif
            </div>
            <div class="fpg-amount">{{ $fmt($total) }}</div>
        </div>

        <x-ui.alert variant="info">
            This fare is paid for before ticketing — your ticket is issued automatically the moment payment is confirmed.
        </x-ui.alert>
    </x-ui.card>

    <x-ui.card title="Payment">
        <div class="fpg-pay">
            <div class="fpg-pay-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </div>
            <div class="fpg-pay-title">Ready when you are</div>
            <div class="fpg-pay-copy">You'll be redirected to our secure payment partner to complete this payment, then brought straight back here.</div>

            <form method="POST" action="{{ route('flights.payment.gateway.process') }}" id="gw-form">
                @csrf
                <x-ui.button type="submit" size="lg" class="fpg-pay-btn" id="gw-btn">
                    <span style="display:flex;align-items:center;gap:8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2.5"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                        Pay {{ $fmt($total) }}
                    </span>
                    <small>Instant ticket issuance on confirmation</small>
                </x-ui.button>
            </form>

            <div class="fpg-trust">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Encrypted and processed by a PCI DSS compliant payment provider
            </div>
        </div>
    </x-ui.card>

    <x-slot:summary>
        <x-ui.card title="Order summary">
            <div class="tw-ui-price">
                <div class="tw-ui-price__row">
                    <span class="tw-ui-price__label">Route</span>
                    @if($isMulti)
                        <span class="tw-ui-price__amount" style="font-variant-numeric:normal;text-align:right;">
                            @foreach($routeLines as $line)
                                <div>{{ $line['route'] }}</div>
                                @if(!empty($line['date']))<span class="tw-ui-price__meta">{{ $line['label'] }} · {{ $line['date'] }}</span>@endif
                            @endforeach
                        </span>
                    @else
                        <span class="tw-ui-price__amount" style="font-variant-numeric:normal;">{{ ($firstSeg['from'] ?? '') }} → {{ ($lastSeg['to'] ?? '') }}</span>
                    @endif
                </div>
                <div class="tw-ui-price__row">
                    <span class="tw-ui-price__label">Fare type</span>
                    <span class="tw-ui-price__amount" style="font-variant-numeric:normal;">{{ $flight['fareType'] ?? 'Standard' }}</span>
                </div>
                <div class="tw-ui-price__row">
                    <span class="tw-ui-price__label">Cabin</span>
                    <span class="tw-ui-price__amount" style="font-variant-numeric:normal;">{{ $cabinLabel }}</span>
                </div>
                @if(!empty($contact['email']))
                    <div class="tw-ui-price__row">
                        <span class="tw-ui-price__label">Contact</span>
                        <span class="tw-ui-price__amount" style="font-variant-numeric:normal;font-size:12px;">{{ $contact['email'] }}</span>
                    </div>
                @endif

                {{-- Fare breakdown — SkyLink's fareBreakdown carries the
                     supplier's own base fare only (no per-type tax split), so
                     a blended total reads more honestly here than a per-type
                     line that would look too low against the Total below.
                     TravelNext's breakdown is the real per-type total fare. --}}
                @php $breakdown = $flight['fareBreakdown'] ?? []; @endphp
                @if(!empty($breakdown) && !$isSkylink)
                    <p class="tw-ui-price__group-title">Fare breakdown</p>
                    @foreach($breakdown as $fb)
                        @php
                            $ptype = match($fb['passengerType']??'ADT'){'ADT'=>'Adult','CHD'=>'Child','INF'=>'Infant',default=>'Pax'};
                            $qty   = $fb['qty'] ?? 1;
                        @endphp
                        <div class="tw-ui-price__row">
                            <span class="tw-ui-price__label">{{ $ptype }} × {{ $qty }}</span>
                            <span class="tw-ui-price__amount">{{ $sym }}{{ number_format(($fb['totalFare']??0) * $qty, 2) }}</span>
                        </div>
                    @endforeach
                @endif

                @if(($extrasTotal ?? 0) > 0)
                    <p class="tw-ui-price__group-title">Extras added</p>
                    @forelse($selectedExtras['baggage'] ?? [] as $baggage)
                        <div class="tw-ui-price__row">
                            <span class="tw-ui-price__label">{{ $baggage['description'] ?? 'Baggage' }} × {{ $baggage['quantity'] ?? 1 }}</span>
                            <span class="tw-ui-price__amount">{{ $fmt($baggage['line_total'] ?? 0) }}</span>
                        </div>
                    @empty
                    @endforelse
                    @forelse($selectedExtras['meal'] ?? [] as $meal)
                        <div class="tw-ui-price__row">
                            <span class="tw-ui-price__label">{{ $meal['description'] ?? 'Meal' }}</span>
                            <span class="tw-ui-price__amount">{{ $fmt($meal['unit_price'] ?? 0) }}</span>
                        </div>
                    @empty
                    @endforelse
                @endif

                <div class="tw-ui-price__total">
                    <span>Total</span>
                    <span class="tw-ui-price__total-amount">{{ $fmt($total) }}</span>
                </div>
            </div>
        </x-ui.card>

        <div class="fpg-badges">
            <x-ui.badge variant="neutral">Secure payment</x-ui.badge>
            <x-ui.badge variant="neutral">SSL encrypted</x-ui.badge>
        </div>
        <p class="fpg-summary-note">Your card details are never seen or stored by TravelWheel.</p>
    </x-slot:summary>

</x-ui.page-shell>

<script>
    document.getElementById('gw-form').addEventListener('submit', function() {
        const btn = document.getElementById('gw-btn');
        btn.disabled = true;
        btn.innerHTML = '<span style="display:flex;align-items:center;gap:8px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="animate-spin"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>Redirecting to payment…</span>';
    });
</script>
@endcomponent
