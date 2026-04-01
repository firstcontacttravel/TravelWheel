{{-- resources/views/livewire/pages/flight/flight-payment-gateway.blade.php --}}
@component('layouts.app', ['title' => 'Secure Payment'])


@php
    $currency = $flight['currency'] ?? 'NGN';
    $sym      = match($currency) { 'NGN' => '₦', 'USD' => '$', 'GBP' => '£', 'EUR' => '€', default => $currency.' ' };
    $fmt      = fn($v) => $sym . number_format((float)$v, 2);
    $total    = $flight['price'] ?? 0;
    $segments = $flight['segments'] ?? [];
    $firstSeg = $segments[0] ?? [];
    $lastSeg  = !empty($segments) ? $segments[count($segments)-1] : [];
    $errors   = session('errors') ?? [];
@endphp

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{--navy:#0a1940;--blue:#1d4ed8;--blue-lt:#eff6ff;--blue-md:#bfdbfe;--green:#059669;--green-lt:#f0fdf4;--red:#dc2626;--red-lt:#fef2f2;--gray-50:#f8fafc;--gray-100:#f1f5f9;--gray-200:#e2e8f0;--gray-300:#cbd5e1;--gray-400:#94a3b8;--gray-500:#64748b;--gray-700:#334155;--gray-900:#0f172a;--font:'Plus Jakarta Sans',sans-serif;--mono:'DM Mono',monospace}
    body{font-family:var(--font);background:var(--gray-50);color:var(--gray-900);font-size:14px;margin-top:110px}
    .gw-wrap{max-width:960px;margin:0 auto;padding:28px 16px 80px;display:grid;grid-template-columns:1fr 340px;gap:22px;align-items:start}
    .gw-card{background:#fff;border:1px solid var(--gray-200);border-radius:14px;box-shadow:0 2px 8px rgba(0,0,0,.07);overflow:hidden}
    .gw-head{padding:18px 24px;background:linear-gradient(135deg,var(--navy) 0%,#1e3a6e 100%);color:#fff;display:flex;align-items:center;gap:12px}
    .gw-head-icon{width:40px;height:40px;border-radius:10px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:20px}
    .gw-head-title{font-size:16px;font-weight:800}
    .gw-head-sub{font-size:12px;opacity:.75;margin-top:2px}
    .gw-lock{display:flex;align-items:center;gap:6px;margin-left:auto;font-size:12px;opacity:.8;background:rgba(255,255,255,.12);padding:6px 12px;border-radius:999px;flex-shrink:0}
    .gw-body{padding:28px 24px}
    /* Notice */
    .gw-notice{display:flex;align-items:flex-start;gap:10px;padding:12px 16px;border-radius:10px;font-size:12.5px;margin-bottom:22px}
    .gw-notice.blue{background:var(--blue-lt);color:var(--blue);border:1px solid var(--blue-md)}
    /* Flight summary strip */
    .gw-flight-strip{background:var(--gray-50);border:1px solid var(--gray-200);border-radius:10px;padding:14px 16px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
    .gw-route{font-size:17px;font-weight:800;color:var(--navy);display:flex;align-items:center;gap:8px}
    .gw-route-sub{font-size:11.5px;color:var(--gray-500);margin-top:3px}
    .gw-amount-tag{background:var(--navy);color:#fff;padding:8px 18px;border-radius:8px;font-size:15px;font-weight:800;font-family:var(--mono);flex-shrink:0}
    /* Payment simulation box */
    .gw-sim-box{border:2px dashed var(--blue-md);border-radius:12px;padding:32px 24px;text-align:center;margin-bottom:28px;background:var(--blue-lt)}
    .gw-sim-icon{font-size:48px;margin-bottom:12px}
    .gw-sim-title{font-size:16px;font-weight:800;color:var(--navy);margin-bottom:6px}
    .gw-sim-sub{font-size:13px;color:var(--gray-500);max-width:340px;margin:0 auto 20px}
    /* Pay button */
    .gw-pay-btn{width:100%;height:56px;background:linear-gradient(135deg,#059669 0%,#10b981 100%);color:#fff;border:none;border-radius:12px;font-size:16px;font-weight:800;cursor:pointer;font-family:var(--font);box-shadow:0 4px 20px rgba(5,150,105,.35);transition:all .2s;display:flex;align-items:center;justify-content:center;gap:10px}
    .gw-pay-btn:hover{transform:translateY(-1px);box-shadow:0 6px 24px rgba(5,150,105,.45)}
    .gw-pay-btn:active{transform:translateY(0)}
    .gw-pay-btn-sub{font-size:11px;opacity:.8;font-weight:500;margin-top:2px}
    .gw-security{display:flex;align-items:center;justify-content:center;gap:6px;font-size:11.5px;color:var(--gray-400);margin-top:14px}
    /* Back */
    .gw-back{display:inline-flex;align-items:center;gap:6px;color:var(--blue);font-size:12.5px;font-weight:600;text-decoration:none;margin-bottom:20px}
    .gw-back:hover{text-decoration:underline}
    /* Right rail */
    .gw-rail-title{font-size:13px;font-weight:800;color:var(--gray-900);margin-bottom:14px}
    .gw-rail-row{display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--gray-100);font-size:13px}
    .gw-rail-row:last-child{border-bottom:none}
    .gw-rail-lbl{color:var(--gray-500)}
    .gw-rail-val{font-weight:700;font-family:var(--mono);font-size:12.5px}
    .gw-rail-total{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-top:2px solid var(--gray-200);margin-top:4px}
    .gw-rail-total-lbl{font-size:14px;font-weight:800;color:var(--navy)}
    .gw-rail-total-val{font-size:22px;font-weight:800;color:var(--navy);font-family:var(--mono)}
    .gw-badges{display:flex;align-items:center;justify-content:center;gap:10px;padding:14px 18px;border-top:1px solid var(--gray-100);flex-wrap:wrap}
    .gw-badge{display:flex;align-items:center;gap:5px;font-size:11px;color:var(--gray-400);font-weight:600}
    /* Error */
    .gw-error{background:var(--red-lt);border:1px solid #fca5a5;border-radius:9px;padding:12px 16px;font-size:13px;color:var(--red);margin-bottom:20px;display:flex;align-items:flex-start;gap:8px}
    @media(max-width:860px){.gw-wrap{grid-template-columns:1fr}}
    @media(max-width:580px){.gw-wrap{padding:12px 10px 60px}.gw-body{padding:20px 16px}.gw-route{font-size:14px}}
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
            <span>{{ session('error') ?? $errors->first('error') }}</span>
        </div>
        @endif

        <div class="gw-card">
            <div class="gw-head">
                <div class="gw-head-icon">💳</div>
                <div>
                    <div class="gw-head-title">Secure Payment</div>
                    <div class="gw-head-sub">Your payment is protected by 256-bit SSL encryption</div>
                </div>
                <div class="gw-lock">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Secured
                </div>
            </div>

            <div class="gw-body">
                {{-- Flight strip --}}
                <div class="gw-flight-strip">
                    <div>
                        <div class="gw-route">
                            {{ $firstSeg['from'] ?? '' }}
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            {{ $lastSeg['to'] ?? '' }}
                        </div>
                        <div class="gw-route-sub">
                            {{ $flight['airline'] ?? '' }}
                            @if(!empty($flight['departDateLabel'])) · {{ $flight['departDateLabel'] }} @endif
                            · {{ $flight['cabin'] ?? 'Economy' }}
                        </div>
                    </div>
                    <div class="gw-amount-tag">{{ $fmt($total) }}</div>
                </div>

                {{-- Info notice --}}
                <div class="gw-notice blue">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span>This is a <strong>Low Cost Carrier (LCC)</strong> ticket. Payment is processed first and your ticket is issued instantly on success.</span>
                </div>

                {{-- Simulation box --}}
                <div class="gw-sim-box">
                    <div class="gw-sim-icon">🔐</div>
                    <div class="gw-sim-title">Payment Gateway</div>
                    <div class="gw-sim-sub">Click the button below to simulate a successful payment and confirm your booking instantly.</div>

                    <form method="POST" action="{{ route('flights.payment.gateway.process') }}" id="gw-form">
                        @csrf
                        <button type="submit" class="gw-pay-btn" id="gw-btn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                            <div>
                                <div>Pay {{ $fmt($total) }} Now</div>
                                <div class="gw-pay-btn-sub">Instant ticket issuance</div>
                            </div>
                        </button>
                    </form>
                </div>

                <div class="gw-security">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    SSL Secured · PCI DSS Compliant · 256-bit Encryption
                </div>
            </div>
        </div>
    </div>

    {{-- ── Right Rail: Order Summary ── --}}
    <aside>
        <div class="gw-card">
            <div style="padding:16px 18px;background:var(--navy);">
                <div style="font-size:15px;font-weight:800;color:#fff;">Order Summary</div>
            </div>
            <div style="padding:14px 18px;">
                <div class="gw-rail-row">
                    <span class="gw-rail-lbl">Route</span>
                    <span class="gw-rail-val" style="font-family:var(--font);font-weight:800;">{{ ($firstSeg['from'] ?? '') }} → {{ ($lastSeg['to'] ?? '') }}</span>
                </div>
                <div class="gw-rail-row">
                    <span class="gw-rail-lbl">Fare Type</span>
                    <span class="gw-rail-val" style="font-family:var(--font);">{{ $flight['fareType'] ?? 'WebFare' }} (LCC)</span>
                </div>
                <div class="gw-rail-row">
                    <span class="gw-rail-lbl">Cabin</span>
                    <span class="gw-rail-val" style="font-family:var(--font);">{{ $flight['cabin'] ?? 'Economy' }}</span>
                </div>
                @if(!empty($contact['email']))
                <div class="gw-rail-row">
                    <span class="gw-rail-lbl">Contact</span>
                    <span class="gw-rail-val" style="font-family:var(--font);font-size:11.5px;">{{ $contact['email'] }}</span>
                </div>
                @endif
            </div>

            {{-- Fare breakdown --}}
            @php $breakdown = $flight['fareBreakdown'] ?? []; @endphp
            @if(!empty($breakdown))
            <div style="padding:0 18px 12px;border-top:1px solid var(--gray-100);padding-top:12px;">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--gray-400);margin-bottom:10px;">Fare Breakdown</div>
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
        btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="animate-spin"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg><div><div>Processing Payment…</div><div style="font-size:11px;opacity:.8">Please wait</div></div>';
    });
</script>
@endcomponent