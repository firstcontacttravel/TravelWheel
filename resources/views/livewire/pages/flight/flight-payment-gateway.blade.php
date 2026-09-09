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

    // This page is reached two ways: TravelNext WebFare fares (pay first, then
    // book — the only fare type routed here) and every SkyLink fare (SkyLink
    // has no hold concept at all, so it always pays first regardless of fare
    // type). "Low Cost Carrier (LCC)" is TravelNext's own fare-type jargon —
    // accurate for the first case, meaningless (and confusing) for the
    // second, so the customer-facing copy below describes the actual
    // mechanic (pay first, ticket issues on confirmation) without leaning on
    // a fare-type label that doesn't apply to both suppliers. Found via user
    // testing: the page previously called every SkyLink fare an "LCC" ticket.
    $isSkylink = ($flight['source'] ?? null) === 'skylink';
@endphp

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{--navy:#0a1940;--blue:#1d4ed8;--blue-lt:#eff6ff;--blue-md:#bfdbfe;--green:#059669;--green-lt:#f0fdf4;--red:#dc2626;--red-lt:#fef2f2;--gray-50:#f8fafc;--gray-100:#f1f5f9;--gray-200:#e2e8f0;--gray-300:#cbd5e1;--gray-400:#94a3b8;--gray-500:#64748b;--gray-700:#334155;--gray-900:#0f172a;--font:'Plus Jakarta Sans',sans-serif;--mono:'DM Mono',monospace}
    body{font-family:var(--font);background:var(--gray-50);color:var(--gray-900);font-size:14px;margin-top:110px}
    .gw-wrap{max-width:960px;margin:0 auto;padding:32px 16px 80px;display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start}
    .gw-card{background:#fff;border:1px solid var(--gray-200);border-radius:12px;box-shadow:0 1px 3px rgba(15,23,42,.06);overflow:hidden}
    .gw-head{padding:20px 26px;background:var(--navy);color:#fff;display:flex;align-items:center;gap:14px}
    .gw-head-icon{width:38px;height:38px;border-radius:9px;background:rgba(255,255,255,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .gw-head-title{font-size:16px;font-weight:700;letter-spacing:-.01em}
    .gw-head-sub{font-size:12px;color:rgba(255,255,255,.65);margin-top:1px}
    .gw-lock{display:flex;align-items:center;gap:6px;margin-left:auto;font-size:11.5px;font-weight:600;color:rgba(255,255,255,.85);border:1px solid rgba(255,255,255,.25);padding:6px 12px;border-radius:999px;flex-shrink:0}
    .gw-body{padding:28px 26px}
    /* Notice */
    .gw-notice{display:flex;align-items:flex-start;gap:10px;padding:13px 16px;border-radius:10px;font-size:13px;line-height:1.5;margin-bottom:22px;background:var(--blue-lt);color:#1e40af;border:1px solid var(--blue-md)}
    .gw-notice svg{flex-shrink:0;margin-top:2px;color:var(--blue)}
    /* Flight summary strip */
    .gw-flight-strip{background:var(--gray-50);border:1px solid var(--gray-200);border-radius:10px;padding:16px 18px;margin-bottom:22px;display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap}
    .gw-route{font-size:16px;font-weight:700;color:var(--navy);display:flex;align-items:center;gap:8px}
    .gw-route-sub{font-size:12px;color:var(--gray-500);margin-top:4px}
    .gw-amount-tag{background:#fff;border:1px solid var(--gray-200);color:var(--navy);padding:9px 16px;border-radius:8px;font-size:15px;font-weight:700;font-family:var(--mono);flex-shrink:0}
    /* Payment panel */
    .gw-pay-panel{border:1px solid var(--gray-200);border-radius:12px;padding:28px 24px;text-align:center;margin-bottom:8px}
    .gw-pay-panel-icon{width:44px;height:44px;border-radius:50%;background:var(--green-lt);color:var(--green);display:flex;align-items:center;justify-content:center;margin:0 auto 14px}
    .gw-pay-title{font-size:15.5px;font-weight:700;color:var(--gray-900);margin-bottom:6px}
    .gw-pay-sub{font-size:12.5px;color:var(--gray-500);max-width:360px;margin:0 auto 22px;line-height:1.5}
    /* Pay button */
    .gw-pay-btn{width:100%;height:54px;background:var(--green);color:#fff;border:none;border-radius:10px;font-size:15.5px;font-weight:700;cursor:pointer;font-family:var(--font);transition:background .15s;display:flex;align-items:center;justify-content:center;gap:10px}
    .gw-pay-btn:hover{background:#047857}
    .gw-pay-btn:disabled{opacity:.7;cursor:not-allowed}
    .gw-pay-btn-sub{font-size:11px;opacity:.85;font-weight:500;margin-top:2px}
    .gw-security{display:flex;align-items:center;justify-content:center;gap:6px;font-size:11.5px;color:var(--gray-400);margin-top:16px}
    /* Back */
    .gw-back{display:inline-flex;align-items:center;gap:6px;color:var(--gray-500);font-size:13px;font-weight:600;text-decoration:none;margin-bottom:18px}
    .gw-back:hover{color:var(--navy)}
    /* Right rail */
    .gw-rail-title{font-size:14.5px;font-weight:700;color:#fff}
    .gw-rail-row{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;padding:9px 0;border-bottom:1px solid var(--gray-100);font-size:13px}
    .gw-rail-row:last-child{border-bottom:none}
    .gw-rail-lbl{color:var(--gray-500)}
    .gw-rail-val{font-weight:600;font-family:var(--mono);font-size:12.5px;text-align:right}
    .gw-rail-total{display:flex;align-items:center;justify-content:space-between;padding:16px 18px;background:var(--gray-50);border-top:1px solid var(--gray-200)}
    .gw-rail-total-lbl{font-size:13px;font-weight:600;color:var(--gray-500)}
    .gw-rail-total-val{font-size:21px;font-weight:700;color:var(--navy);font-family:var(--mono)}
    .gw-badges{display:flex;align-items:center;justify-content:center;gap:16px;padding:14px 18px;border-top:1px solid var(--gray-100)}
    .gw-badge{display:flex;align-items:center;gap:5px;font-size:11px;color:var(--gray-400);font-weight:600}
    .gw-badge svg{color:var(--gray-300)}
    /* Error */
    .gw-error{background:var(--red-lt);border:1px solid #fca5a5;border-radius:9px;padding:12px 16px;font-size:13px;color:var(--red);margin-bottom:20px;display:flex;align-items:flex-start;gap:8px}
    @media(max-width:860px){.gw-wrap{grid-template-columns:1fr}}
    @media(max-width:580px){.gw-wrap{padding:14px 12px 60px}.gw-body{padding:22px 18px}.gw-route{font-size:14px}}
</style>

<div class="gw-wrap">

    {{-- ── Main Payment Column ── --}}
    <div>
        <a href="{{ route('flights.booking') }}" class="gw-back">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Back to Booking
        </a>

        @if(session('error') || $errors->has('error'))
        <div class="gw-error">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span>{{ session('error') ?? $errors->first('error') ?? 'Payment error occurred' }}</span>
        </div>
        @endif

        <div class="gw-card">
            <div class="gw-head">
                <div class="gw-head-icon">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2.5"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                </div>
                <div>
                    <div class="gw-head-title">Payment</div>
                    <div class="gw-head-sub">Booking ref pending confirmation</div>
                </div>
                <div class="gw-lock">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Secured
                </div>
            </div>

            <div class="gw-body">
                {{-- Flight strip --}}
                <div class="gw-flight-strip">
                    <div>
                        @if($isMulti)
                            <div style="display:flex;flex-direction:column;gap:6px;">
                                @foreach($routeLines as $line)
                                    <div class="gw-route" style="font-size:14.5px;">
                                        <span>{{ $line['route'] }}</span>
                                        <span style="font-size:11px;font-weight:600;color:var(--blue);background:var(--blue-lt);padding:2px 8px;border-radius:999px;">{{ $line['label'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                            <div class="gw-route-sub">
                                {{ $flight['airline'] ?? '' }} · {{ count($routeLines) }} legs · {{ $cabinLabel }}
                            </div>
                        @else
                        <div class="gw-route">
                            {{ $firstSeg['from'] ?? '' }}
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            {{ $lastSeg['to'] ?? '' }}
                        </div>
                        <div class="gw-route-sub">
                            {{ $flight['airline'] ?? '' }}
                            @if(!empty($flight['departDateLabel'])) · {{ $flight['departDateLabel'] }} @endif
                            · {{ $cabinLabel }}
                        </div>
                        @endif
                    </div>
                    <div class="gw-amount-tag">{{ $fmt($total) }}</div>
                </div>

                {{-- Info notice — describes the actual pay-first mechanic without
                     leaning on TravelNext's "LCC" fare-type jargon, since this
                     page is also reached by SkyLink fares that aren't LCC fares
                     at all. --}}
                <div class="gw-notice">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span>This fare is paid for before ticketing — your ticket is issued automatically the moment payment is confirmed.</span>
                </div>

                {{-- Payment panel --}}
                <div class="gw-pay-panel">
                    <div class="gw-pay-panel-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                    <div class="gw-pay-title">Complete your payment</div>
                    <div class="gw-pay-sub">You'll be redirected to our secure payment partner to complete this payment, then brought straight back here.</div>

                    <form method="POST" action="{{ route('flights.payment.gateway.process') }}" id="gw-form">
                        @csrf
                        <button type="submit" class="gw-pay-btn" id="gw-btn">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2.5"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                            <div>
                                <div>Pay {{ $fmt($total) }}</div>
                                <div class="gw-pay-btn-sub">Instant ticket issuance on confirmation</div>
                            </div>
                        </button>
                    </form>
                </div>

                <div class="gw-security">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Encrypted and processed by a PCI DSS compliant payment provider
                </div>
            </div>
        </div>
    </div>

    {{-- ── Right Rail: Order Summary ── --}}
    <aside>
        <div class="gw-card">
            <div style="padding:16px 18px;background:var(--navy);">
                <div class="gw-rail-title">Order Summary</div>
            </div>
            <div style="padding:6px 18px;">
                <div class="gw-rail-row">
                    <span class="gw-rail-lbl">Route</span>
                    @if($isMulti)
                    <span class="gw-rail-val" style="font-family:var(--font);">
                        @foreach($routeLines as $line)
                            <div>{{ $line['route'] }}</div>
                            @if(!empty($line['date']))
                                <div style="font-size:11px;color:var(--gray-400);font-weight:500;">{{ $line['label'] }} · {{ $line['date'] }}</div>
                            @endif
                        @endforeach
                    </span>
                    @else
                    <span class="gw-rail-val" style="font-family:var(--font);">{{ ($firstSeg['from'] ?? '') }} → {{ ($lastSeg['to'] ?? '') }}</span>
                    @endif
                </div>
                <div class="gw-rail-row">
                    <span class="gw-rail-lbl">Fare Type</span>
                    <span class="gw-rail-val" style="font-family:var(--font);">{{ $flight['fareType'] ?? 'Standard' }}</span>
                </div>
                <div class="gw-rail-row">
                    <span class="gw-rail-lbl">Cabin</span>
                    <span class="gw-rail-val" style="font-family:var(--font);">{{ $cabinLabel }}</span>
                </div>
                @if(!empty($contact['email']))
                <div class="gw-rail-row">
                    <span class="gw-rail-lbl">Contact</span>
                    <span class="gw-rail-val" style="font-family:var(--font);font-size:11.5px;">{{ $contact['email'] }}</span>
                </div>
                @endif
            </div>

            {{-- Fare breakdown — SkyLink's fareBreakdown carries the supplier's
                 own base fare only (no per-type tax split), so a blended total
                 reads more honestly here than a per-type line that would look
                 too low against the Total below. TravelNext's breakdown is the
                 real per-type total fare and is shown as such. --}}
            @php $breakdown = $flight['fareBreakdown'] ?? []; @endphp
            @if(!empty($breakdown) && !$isSkylink)
            <div style="padding:10px 18px 12px;border-top:1px solid var(--gray-100);">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--gray-400);margin-bottom:8px;">Fare Breakdown</div>
                @foreach($breakdown as $fb)
                    @php
                        $ptype = match($fb['passengerType']??'ADT'){'ADT'=>'Adult','CHD'=>'Child','INF'=>'Infant',default=>'Pax'};
                        $qty   = $fb['qty'] ?? 1;
                    @endphp
                    <div class="gw-rail-row">
                        <span class="gw-rail-lbl">{{ $ptype }} × {{ $qty }}</span>
                        <span class="gw-rail-val">{{ $sym }}{{ number_format(($fb['totalFare']??0) * $qty, 2) }}</span>
                    </div>
                @endforeach
            </div>
            @endif

            {{-- Extras section --}}
            @if(($extrasTotal ?? 0) > 0)
            <div style="padding:10px 18px 12px;border-top:1px solid var(--gray-100);">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--gray-400);margin-bottom:8px;">Extras Added</div>

                {{-- Baggage items --}}
                @forelse($selectedExtras['baggage'] ?? [] as $baggage)
                    <div class="gw-rail-row">
                        <span class="gw-rail-lbl">{{ $baggage['description'] ?? 'Baggage' }} × {{ $baggage['quantity'] ?? 1 }}</span>
                        <span class="gw-rail-val">{{ $fmt($baggage['line_total'] ?? 0) }}</span>
                    </div>
                @empty
                @endforelse

                {{-- Meal items --}}
                @forelse($selectedExtras['meal'] ?? [] as $meal)
                    <div class="gw-rail-row">
                        <span class="gw-rail-lbl">{{ $meal['description'] ?? 'Meal' }}</span>
                        <span class="gw-rail-val">{{ $fmt($meal['unit_price'] ?? 0) }}</span>
                    </div>
                @empty
                @endforelse

                <div class="gw-rail-row" style="padding-top:8px;border-top:1px dashed var(--gray-200);margin-top:6px;padding-bottom:0;">
                    <span class="gw-rail-lbl" style="font-weight:600;">Extras Subtotal</span>
                    <span class="gw-rail-val" style="font-weight:600;">{{ $fmt($extrasTotal) }}</span>
                </div>
            </div>
            @endif

            <div class="gw-rail-total">
                <span class="gw-rail-total-lbl">Total</span>
                <span class="gw-rail-total-val">{{ $fmt($total) }}</span>
            </div>

            <div class="gw-badges">
                <span class="gw-badge">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Secure Payment
                </span>
                <span class="gw-badge">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    SSL Encrypted
                </span>
            </div>
        </div>
    </aside>

</div>

<script>
    document.getElementById('gw-form').addEventListener('submit', function() {
        const btn = document.getElementById('gw-btn');
        btn.disabled = true;
        btn.innerHTML = '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="animate-spin"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg><div><div>Redirecting to payment…</div><div style="font-size:11px;opacity:.85">Please wait</div></div>';
    });
</script>
@endcomponent
