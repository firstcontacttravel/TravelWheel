{{-- resources/views/livewire/pages/flight/flight-payment-options.blade.php --}}
@component('layouts.app', ['title' => 'Choose Payment Method'])

@php
    $currency    = $flight['currency'] ?? 'NGN';
    $sym         = match($currency) { 'NGN' => '₦', 'USD' => '$', 'GBP' => '£', 'EUR' => '€', default => $currency.' ' };
    $fmt         = fn($v) => $sym . number_format((float)$v, 2);
    $total       = $flight['price'] ?? 0;
    $segments    = $flight['segments'] ?? [];
    $firstSeg    = $segments[0] ?? [];
    $lastSeg     = !empty($segments) ? $segments[count($segments)-1] : [];
    $fareType    = $flight['fareType'] ?? 'Public';
    $breakdown   = $flight['fareBreakdown'] ?? [];

    // Ticketing deadline
    $tktFormatted = '';
    $tktHours     = 0;
    if ($tktTimeLimit) {
        try {
            $tktDt        = \Carbon\Carbon::parse($tktTimeLimit);
            $tktFormatted = $tktDt->format('D, d M Y \a\t H:i');
            $tktHours     = max(0, (int) now()->diffInHours($tktDt, false));
        } catch (\Throwable $e) {}
    }

    // Dummy bank accounts
    $bankAccounts = [
        ['bank' => 'Access Bank', 'account_number' => '0123456789', 'account_name' => 'Travelwheel Limited', 'sort_code' => '044'],
        ['bank' => 'Zenith Bank',  'account_number' => '2109876543', 'account_name' => 'Travelwheel Limited', 'sort_code' => '057'],
        ['bank' => 'GTBank',       'account_number' => '0156789234', 'account_name' => 'Travelwheel Limited', 'sort_code' => '058'],
    ];
@endphp

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{--navy:#0a1940;--blue:#1d4ed8;--blue-lt:#eff6ff;--blue-md:#bfdbfe;--green:#059669;--green-lt:#f0fdf4;--amber:#d97706;--amber-lt:#fff7ed;--red:#dc2626;--red-lt:#fef2f2;--gray-50:#f8fafc;--gray-100:#f1f5f9;--gray-200:#e2e8f0;--gray-300:#cbd5e1;--gray-400:#94a3b8;--gray-500:#64748b;--gray-700:#334155;--gray-900:#0f172a;--font:'Plus Jakarta Sans',sans-serif;--mono:'DM Mono',monospace}
    body{font-family:var(--font);background:var(--gray-50);color:var(--gray-900);font-size:14px;margin-top:110px}
    .po-wrap{max-width:1100px;margin:0 auto;padding:28px 16px 80px}
    /* Hold banner */
    .po-hold-banner{background:linear-gradient(135deg,#78350f 0%,#d97706 100%);color:#fff;border-radius:14px;padding:22px 28px;margin-bottom:24px;display:flex;align-items:flex-start;gap:18px}
    .po-hold-icon{font-size:36px;flex-shrink:0}
    .po-hold-title{font-size:18px;font-weight:800;margin-bottom:4px}
    .po-hold-sub{font-size:13px;opacity:.9;line-height:1.6}
    .po-hold-ref{display:inline-flex;align-items:center;gap:8px;margin-top:10px;padding:7px 14px;background:rgba(255,255,255,.15);border-radius:8px;font-size:13px;font-weight:700;font-family:var(--mono)}
    .po-hold-deadline{display:inline-flex;align-items:center;gap:6px;margin-top:8px;margin-left:8px;padding:7px 14px;background:rgba(255,255,255,.12);border-radius:8px;font-size:12px;font-weight:600}
    /* Layout */
    .po-grid{display:grid;grid-template-columns:1fr 320px;gap:22px;align-items:start}
    .po-main{display:flex;flex-direction:column;gap:16px}
    /* Section title */
    .po-section-title{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--gray-400);margin-bottom:14px}
    /* Payment option card */
    .po-option{background:#fff;border:2px solid var(--gray-200);border-radius:14px;overflow:hidden;transition:border-color .2s,box-shadow .2s;cursor:pointer}
    .po-option:hover{border-color:var(--blue-md);box-shadow:0 4px 20px rgba(29,78,216,.1)}
    .po-option.active{border-color:var(--blue);box-shadow:0 4px 20px rgba(29,78,216,.15)}
    .po-option-head{display:flex;align-items:center;gap:14px;padding:18px 20px;user-select:none}
    .po-option-radio{width:20px;height:20px;border-radius:50%;border:2px solid var(--gray-300);display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .2s}
    .po-option.active .po-option-radio{border-color:var(--blue);background:var(--blue)}
    .po-option-radio-dot{width:8px;height:8px;border-radius:50%;background:#fff;transform:scale(0);transition:transform .15s}
    .po-option.active .po-option-radio-dot{transform:scale(1)}
    .po-option-icon{width:44px;height:44px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0}
    .po-option-icon.bank{background:#e0f2fe;} .po-option-icon.gw{background:#f0fdf4;} .po-option-icon.flex{background:#fef3c7;}
    .po-option-title{font-size:15px;font-weight:800;color:var(--gray-900)}
    .po-option-sub{font-size:12px;color:var(--gray-500);margin-top:2px}
    .po-option-badge{margin-left:auto;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;flex-shrink:0}
    .po-option-badge.instant{background:var(--green-lt);color:var(--green)}
    .po-option-badge.manual{background:var(--amber-lt);color:var(--amber)}
    .po-option-badge.soon{background:var(--gray-100);color:var(--gray-400)}
    /* Option body (collapsible) */
    .po-option-body{padding:0 20px 20px;border-top:1px solid var(--gray-100);display:none}
    .po-option.active .po-option-body{display:block}
    /* Bank accounts */
    .po-bank-list{display:flex;flex-direction:column;gap:10px;margin-bottom:18px}
    .po-bank-card{background:var(--gray-50);border:1px solid var(--gray-200);border-radius:10px;padding:14px 16px;position:relative}
    .po-bank-name{font-size:11.5px;font-weight:700;color:var(--gray-400);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px}
    .po-bank-acct{font-size:18px;font-weight:800;color:var(--navy);font-family:var(--mono);letter-spacing:.06em}
    .po-bank-holder{font-size:12px;color:var(--gray-500);margin-top:3px}
    .po-copy-btn{position:absolute;top:12px;right:12px;padding:5px 12px;border:1.5px solid var(--gray-200);border-radius:7px;background:#fff;font-size:11.5px;font-weight:700;color:var(--gray-500);cursor:pointer;transition:all .15s;font-family:var(--font)}
    .po-copy-btn:hover{border-color:var(--blue);color:var(--blue);background:var(--blue-lt)}
    /* Reference input */
    .po-ref-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--gray-400);margin-bottom:6px}
    .po-ref-input{width:100%;height:44px;padding:0 14px;border:1.5px solid var(--gray-200);border-radius:9px;font-size:14px;font-family:var(--font);outline:none;transition:border-color .15s;background:var(--gray-50)}
    .po-ref-input:focus{border-color:var(--blue);background:#fff;box-shadow:0 0 0 3px rgba(59,130,246,.1)}
    /* Action buttons */
    .po-action-btn{width:100%;height:50px;border:none;border-radius:11px;font-size:14px;font-weight:800;cursor:pointer;font-family:var(--font);display:flex;align-items:center;justify-content:center;gap:9px;transition:all .2s}
    .po-action-btn.bank{background:#0a1940;color:#fff;} .po-action-btn.bank:hover{background:#0f2460}
    .po-action-btn.gw{background:linear-gradient(135deg,#059669 0%,#10b981 100%);color:#fff;box-shadow:0 4px 16px rgba(5,150,105,.3)} .po-action-btn.gw:hover{transform:translateY(-1px)}
    .po-action-btn.flex-btn{background:var(--gray-100);color:var(--gray-400);cursor:not-allowed}
    /* Notice */
    .po-notice{display:flex;align-items:flex-start;gap:9px;padding:11px 14px;border-radius:9px;font-size:12.5px;margin-bottom:14px}
    .po-notice.warn{background:var(--amber-lt);color:var(--amber);border:1px solid #fed7aa}
    .po-notice.info{background:var(--blue-lt);color:var(--blue);border:1px solid var(--blue-md)}
    /* Right rail */
    .po-rail-card{background:#fff;border:1px solid var(--gray-200);border-radius:14px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.07);position:sticky;top:20px}
    .po-rail-head{padding:14px 18px;background:var(--navy)}
    .po-rail-title{font-size:15px;font-weight:800;color:#fff}
    .po-rail-body{padding:14px 18px}
    .po-rail-row{display:flex;align-items:flex-start;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--gray-100);font-size:12.5px;gap:12px}
    .po-rail-row:last-child{border-bottom:none}
    .po-rail-lbl{color:var(--gray-500)}
    .po-rail-val{font-weight:700;font-family:var(--mono);font-size:12px;text-align:right}
    .po-rail-total-row{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-top:2px solid var(--gray-200)}
    .po-rail-total-lbl{font-size:14px;font-weight:800;color:var(--navy)}
    .po-rail-total-val{font-size:22px;font-weight:800;color:var(--navy);font-family:var(--mono)}
    @media(max-width:900px){.po-grid{grid-template-columns:1fr}.po-rail-card{position:static}}
    @media(max-width:580px){.po-wrap{padding:12px 10px 60px}.po-hold-banner{flex-direction:column;gap:10px}}
</style>

<div class="po-wrap" x-data="{ activeOption: '' }">

    {{-- ── On-Hold Banner ── --}}
    <div class="po-hold-banner">
        <div class="po-hold-icon">✈️</div>
        <div style="flex:1">
            <div class="po-hold-title">Your seat is reserved — complete payment to confirm your ticket</div>
            <div class="po-hold-sub">
                Your booking is currently <strong>on hold</strong>. Your seats are secured and waiting. 
                Complete your payment before the deadline to have your e-ticket issued immediately.
            </div>
            <div style="display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin-top:10px;">
                @if($uniqueId)
                <div class="po-hold-ref">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                    Ref: {{ $uniqueId }}
                </div>
                @endif
                @if($tktFormatted)
                <div class="po-hold-deadline">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="6" x2="12" y2="12"/><line x1="16" y1="14" x2="12" y2="12"/></svg>
                    Pay before: {{ $tktFormatted }}
                    @if($tktHours > 0) ({{ $tktHours }} hrs remaining) @endif
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="po-grid">

        {{-- ── Main: Payment Options ── --}}
        <div class="po-main">

            <div class="po-section-title">Choose how you'd like to pay</div>
            @if($errors->has('flex_error'))
                <div class="po-notice" style="background:var(--red-lt);color:var(--red);border:1px solid #fca5a5;margin-bottom:16px;">
                    {{ $errors->first('flex_error') }}
                </div>
            @endif
                    {{-- ── Option 1: Direct Bank Transfer ── --}}
            <div class="po-option" :class="{ active: activeOption === 'bank' }" @click="activeOption = 'bank'">
                <div class="po-option-head">
                    <div class="po-option-radio"><div class="po-option-radio-dot"></div></div>
                    <div class="po-option-icon bank">🏦</div>
                    <div>
                        <div class="po-option-title">Direct Bank Transfer</div>
                        <div class="po-option-sub">Transfer to our account and confirm payment</div>
                    </div>
                    <span class="po-option-badge manual">Manual</span>
                </div>
                <div class="po-option-body">
                    <div class="po-notice warn">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span>Transfer the exact amount of <strong>{{ $fmt($total) }}</strong>. Your ticket will be issued once payment is verified (typically within 2–4 hours).</span>
                    </div>

                    <div class="po-bank-list">
                        @foreach($bankAccounts as $acct)
                        <div class="po-bank-card">
                            <div class="po-bank-name">{{ $acct['bank'] }}</div>
                            <div class="po-bank-acct">{{ $acct['account_number'] }}</div>
                            <div class="po-bank-holder">{{ $acct['account_name'] }}</div>
                            <button type="button" class="po-copy-btn"
                                onclick="navigator.clipboard.writeText('{{ $acct['account_number'] }}').then(()=>{ this.textContent='Copied!'; setTimeout(()=>this.textContent='Copy',1500) })">
                                Copy
                            </button>
                        </div>
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('flights.payment.bank-transfer') }}">
                        @csrf
                        <div class="po-ref-label">Payment Reference (optional)</div>
                        <input class="po-ref-input" type="text" name="payment_reference"
                               placeholder="e.g. bank transaction ref or your name"
                               style="margin-bottom:14px">

                        <button type="submit" class="po-action-btn bank">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                            I Have Made Payment
                        </button>
                    </form>
                </div>
            </div>

            {{-- ── Option 2: Payment Gateway ── --}}
            <div class="po-option" :class="{ active: activeOption === 'gateway' }" @click="activeOption = 'gateway'">
                <div class="po-option-head">
                    <div class="po-option-radio"><div class="po-option-radio-dot"></div></div>
                    <div class="po-option-icon gw">💳</div>
                    <div>
                        <div class="po-option-title">Pay Online</div>
                        <div class="po-option-sub">Card, bank transfer or USSD — instant confirmation</div>
                    </div>
                    <span class="po-option-badge instant">Instant</span>
                </div>
                <div class="po-option-body">
                    <div class="po-notice info">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span>Payment is processed securely. Your ticket will be issued <strong>immediately</strong> after successful payment.</span>
                    </div>

                    <form method="POST" action="{{ route('flights.payment.gateway-ticket') }}" id="gw-ticket-form">
                        @csrf
                        <button type="submit" class="po-action-btn gw" id="gw-ticket-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                            Pay {{ $fmt($total) }} &amp; Issue Ticket Now
                        </button>
                    </form>
                </div>
            </div>

            {{-- ── Option 3: Travel Flex ── --}}
            <div class="po-option" :class="{ active: activeOption === 'flex' }" @click="activeOption = 'flex'">
                <div class="po-option-head">
                    <div class="po-option-radio"><div class="po-option-radio-dot"></div></div>
                    <div class="po-option-icon flex">📆</div>
                    <div>
                        <div class="po-option-title">Travel Flex — Pay in Instalments</div>
                        <div class="po-option-sub">30% down payment, balance over time</div>
                    </div>
                    @php
                        $departDT     = $flight['segments'][0]['departDT'] ?? null;
                        $daysToDepart = $departDT ? max(0, (int) now()->diffInDays(\Carbon\Carbon::parse($departDT), false)) : 0;
                        $flexEligible = $daysToDepart >= 14;
                        $isRefundable  = $flight['isRefundable'] ?? false;   // ← ADD THIS
                        $flexAvailable = $flexEligible && $isRefundable; 
                    @endphp
                    @if(!$isRefundable)
                    <div style="text-align:center;padding:22px 0;">
                        <div style="font-size:32px;margin-bottom:10px;">🔒</div>
                        <div style="font-size:14px;font-weight:700;color:var(--gray-700);margin-bottom:6px;">Non-Refundable Ticket</div>
                        <div style="font-size:12.5px;color:var(--gray-500);">TravelFlex is only available for <strong>refundable tickets</strong>. This fare type does not qualify. Please choose another payment method.</div>
                    </div>
                    @endif
                </div>
                <div class="po-option-body">
                    @if($flexEligible)
                    <div class="po-notice info" style="margin-top:12px;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span>Pay 30% down today, spread the rest over your chosen plan. 5% interest per repayment interval. Powered by a licensed third-party lender.</span>
                    </div>
                    <a href="{{ route('flights.travelflex') }}" class="po-action-btn gw" style="margin-top:12px;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:8px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Apply for TravelFlex
                    </a>
                    @else
                    <div style="text-align:center;padding:22px 0;">
                        <div style="font-size:32px;margin-bottom:10px;">⏰</div>
                        <div style="font-size:14px;font-weight:700;color:var(--gray-700);margin-bottom:6px;">Not Eligible for This Booking</div>
                        <div style="font-size:12.5px;color:var(--gray-500);">TravelFlex requires at least <strong>14 days</strong> before travel. Your flight departs in {{ $daysToDepart }} day(s). Please choose another payment method.</div>
                    </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- ── Right Rail ── --}}
        <aside>
            <div class="po-rail-card">
                <div class="po-rail-head">
                    <div class="po-rail-title">Booking Summary</div>
                </div>
                <div class="po-rail-body">
                    <div class="po-rail-row">
                        <span class="po-rail-lbl">Route</span>
                        <span class="po-rail-val" style="font-family:var(--font);font-weight:800;font-size:13px;">{{ ($firstSeg['from']??'') }} → {{ ($lastSeg['to']??'') }}</span>
                    </div>
                    @if($uniqueId)
                    <div class="po-rail-row">
                        <span class="po-rail-lbl">Booking Ref</span>
                        <span class="po-rail-val" style="color:var(--blue)">{{ $uniqueId }}</span>
                    </div>
                    @endif
                    <div class="po-rail-row">
                        <span class="po-rail-lbl">Fare Type</span>
                        <span class="po-rail-val" style="font-family:var(--font)">{{ $fareType }}</span>
                    </div>
                    <div class="po-rail-row">
                        <span class="po-rail-lbl">Airline</span>
                        <span class="po-rail-val" style="font-family:var(--font)">{{ $flight['airline'] ?? '—' }}</span>
                    </div>
                    <div class="po-rail-row">
                        <span class="po-rail-lbl">Cabin</span>
                        <span class="po-rail-val" style="font-family:var(--font)">{{ $flight['cabin'] ?? 'Economy' }}</span>
                    </div>
                    @if($tktFormatted)
                    <div class="po-rail-row" style="background:#fff7ed;margin:4px -18px;padding:8px 18px;border-radius:0">
                        <span class="po-rail-lbl" style="color:var(--amber);font-weight:700">⏰ Pay before</span>
                        <span class="po-rail-val" style="color:var(--amber);font-family:var(--font);font-size:11px">{{ $tktFormatted }}</span>
                    </div>
                    @endif
                </div>

                @if(!empty($breakdown))
                <div style="padding:0 18px 12px;border-top:1px solid var(--gray-100);padding-top:12px;">
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--gray-400);margin-bottom:8px">Fare Breakdown</div>
                    @foreach($breakdown as $fb)
                        @php $ptype = match($fb['passengerType']??'ADT'){'ADT'=>'Adult','CHD'=>'Child','INF'=>'Infant',default=>'Pax'}; @endphp
                        <div class="po-rail-row">
                            <span class="po-rail-lbl">{{ $ptype }} × {{ $fb['qty']??1 }}</span>
                            <span class="po-rail-val">{{ $sym }}{{ number_format(($fb['totalFare']??0)*($fb['qty']??1), 2) }}</span>
                        </div>
                    @endforeach
                </div>
                @endif

                <div class="po-rail-total-row">
                    <span class="po-rail-total-lbl">Total</span>
                    <span class="po-rail-total-val">{{ $fmt($total) }}</span>
                </div>
            </div>
        </aside>

    </div>
</div>

<script src="//unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
    document.getElementById('gw-ticket-form').addEventListener('submit', function() {
        const btn = document.getElementById('gw-ticket-btn');
        btn.disabled = true;
        btn.textContent = 'Processing Payment…';
    });
</script>
@endcomponent