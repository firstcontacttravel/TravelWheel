{{-- resources/views/livewire/pages/flight/flight-travelflex.blade.php --}}
@component('layouts.app', ['title' => 'TravelFlex — Pay in Instalments'])

@php
    $bookingFlight  = session('bookingFlight', []);
    $mappedFlight   = $bookingFlight['flight'] ?? $bookingFlight;
    $segments       = $mappedFlight['segments'] ?? [];
    $firstSeg       = $segments[0] ?? [];
    $lastSeg        = !empty($segments) ? $segments[count($segments)-1] : [];
    $currency       = $mappedFlight['currency'] ?? 'NGN';
    $sym            = match($currency) { 'NGN' => '₦', 'USD' => '$', 'GBP' => '£', 'EUR' => '€', default => $currency.' ' };
    $totalPrice     = (float) ($mappedFlight['price'] ?? 0);
    $fareType       = $mappedFlight['fareType'] ?? 'Public';
    $airline        = $mappedFlight['airline']  ?? '';
    $cabin          = $mappedFlight['cabin']    ?? 'Economy';
    $uniqueId       = session('bookingUniqueId', '');
    $tktTimeLimit   = session('bookingTktTimeLimit', '');
    $contact        = session('bookingContact', []);
    $dbId           = session('flightBookingDbId');

    // Departure date from first segment
    $departDT        = $firstSeg['departDT'] ?? '';
    $departDateLabel = !empty($firstSeg['departDate']) ? $firstSeg['departDate'] : ($departDT ? \Carbon\Carbon::parse($departDT)->format('D, d M Y') : '');
    // ISO date for JS (YYYY-MM-DD)
    $departDateISO   = $departDT ? \Carbon\Carbon::parse($departDT)->format('Y-m-d') : '';
    // Days from today to departure
    $daysToDepart    = $departDT ? max(0, (int) now()->diffInDays(\Carbon\Carbon::parse($departDT), false)) : 0;
    // Minimum 14 days required
    $eligible        = $daysToDepart >= 14;

    $breakdown = $mappedFlight['fareBreakdown'] ?? [];

    // Bank accounts (same as payment options)
    $bankAccounts = [
        ['bank' => 'Access Bank', 'account_number' => '0123456789', 'account_name' => 'Travelwheel Limited'],
        ['bank' => 'Zenith Bank',  'account_number' => '2109876543', 'account_name' => 'Travelwheel Limited'],
        ['bank' => 'GTBank',       'account_number' => '0156789234', 'account_name' => 'Travelwheel Limited'],
    ];
@endphp

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

<style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
        --tf-navy:    #0a1940;
        --tf-blue:    #1d4ed8;
        --tf-blue-lt: #eff6ff;
        --tf-blue-md: #bfdbfe;
        --tf-indigo:  #4338ca;
        --tf-purple:  #7c3aed;
        --tf-green:   #059669;
        --tf-green-lt:#f0fdf4;
        --tf-amber:   #d97706;
        --tf-amber-lt:#fff7ed;
        --tf-red:     #dc2626;
        --tf-red-lt:  #fef2f2;
        --gray-50:    #f8fafc;
        --gray-100:   #f1f5f9;
        --gray-200:   #e2e8f0;
        --gray-300:   #cbd5e1;
        --gray-400:   #94a3b8;
        --gray-500:   #64748b;
        --gray-700:   #334155;
        --gray-900:   #0f172a;
        --font:       'Plus Jakarta Sans', sans-serif;
        --mono:       'DM Mono', monospace;
    }
    body { font-family: var(--font); background: var(--gray-50); color: var(--gray-900); font-size: 14px; margin-top: 110px; }

    /* ── Layout ── */
    .tf-outer { max-width: 1060px; margin: 0 auto; padding: 28px 16px 80px; }
    .tf-grid  { display: grid; grid-template-columns: 1fr 300px; gap: 22px; align-items: start; }
    .tf-main  { display: flex; flex-direction: column; gap: 0; }

    /* ── Header gradient card ── */
    .tf-hero { background: linear-gradient(135deg, var(--tf-navy) 0%, #312e81 50%, var(--tf-indigo) 100%); border-radius: 16px; padding: 26px 28px; margin-bottom: 22px; color: #fff; position: relative; overflow: hidden; }
    .tf-hero::before { content: ''; position: absolute; top: -40px; right: -40px; width: 200px; height: 200px; background: radial-gradient(circle, rgba(124,58,237,.35) 0%, transparent 70%); pointer-events: none; }
    .tf-hero-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(255,255,255,.15); color: white; border-radius: 999px; font-size: 11px; font-weight: 700; letter-spacing: .05em; margin-bottom: 10px; }
    .tf-hero-title { font-size: 22px; font-weight: 800; margin-bottom: 6px; color: white; }
    .tf-hero-sub   { font-size: 13px; opacity: .85; line-height: 1.65; max-width: 480px; color: white; }

    /* ── Progress ── */
    .tf-progress-wrap { background: rgba(255,255,255,.12); border-radius: 999px; height: 6px; margin-top: 18px; overflow: hidden; }
    .tf-progress-bar  { height: 100%; border-radius: 999px; background: linear-gradient(90deg, #a5b4fc, #fff); transition: width .45s cubic-bezier(.2,.9,.3,1); }

    /* ── Step label ── */
    .tf-step-label { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: var(--gray-400); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
    .tf-step-label span { width: 22px; height: 22px; border-radius: 50%; background: var(--tf-blue); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 800; }

    /* ── Main card ── */
    .tf-card { background: #fff; border: 1px solid var(--gray-200); border-radius: 14px; padding: 24px 24px; box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 0; }
    .tf-card + .tf-card { margin-top: 0; border-top: none; border-radius: 0 0 14px 14px; }
    .tf-step { display: none; }
    .tf-step.active { display: block; animation: tfFadeIn .3s ease both; }
    @keyframes tfFadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }

    /* ── Ineligible notice ── */
    .tf-ineligible { background: var(--tf-amber-lt); border: 1px solid #fed7aa; border-radius: 12px; padding: 20px 22px; display: flex; align-items: flex-start; gap: 16px; }
    .tf-ineligible-icon { font-size: 32px; flex-shrink: 0; }

    /* ── Disclaimer ── */
    .tf-disclaimer-box { background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 10px; padding: 18px 20px; max-height: 360px; overflow-y: auto; font-size: 12.5px; line-height: 1.75; color: var(--gray-700); margin-bottom: 20px; }
    .tf-disclaimer-box h4 { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: var(--gray-400); margin: 14px 0 6px; }
    .tf-disclaimer-box h4:first-child { margin-top: 0; }
    .tf-disclaimer-box p { margin-bottom: 8px; }
    .tf-agree-row { display: flex; align-items: flex-start; gap: 12px; padding: 14px; background: var(--tf-blue-lt); border: 1.5px solid var(--tf-blue-md); border-radius: 10px; cursor: pointer; }
    .tf-agree-row input[type=checkbox] { width: 18px; height: 18px; accent-color: var(--tf-blue); flex-shrink: 0; margin-top: 1px; cursor: pointer; }
    .tf-agree-row label { font-size: 13px; font-weight: 600; color: var(--tf-blue); line-height: 1.5; cursor: pointer; }

    /* ── Form fields ── */
    .tf-field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 20px; }
    .tf-field-full  { grid-column: 1 / -1; }
    .tf-field { display: flex; flex-direction: column; gap: 5px; }
    .tf-label { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--gray-400); }
    .tf-label .tf-req { color: var(--tf-red); margin-left: 2px; }
    .tf-input, .tf-select {
        height: 46px; padding: 0 14px; border: 1.5px solid var(--gray-200); border-radius: 10px;
        font-size: 14px; color: var(--gray-900); background: var(--gray-50); outline: none;
        font-family: var(--font); transition: border-color .15s, box-shadow .15s; width: 100%;
    }
    .tf-input:focus, .tf-select:focus { border-color: var(--tf-blue); background: #fff; box-shadow: 0 0 0 3px rgba(59,130,246,.1); }
    .tf-input[readonly], .tf-input[disabled] { background: #eef2f7; color: var(--gray-500); cursor: not-allowed; }
    .tf-select { appearance: none; cursor: pointer; padding-right: 32px; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; background-color: var(--gray-50); }
    .tf-select:disabled { background-color: #eef2f7; color: var(--gray-500); cursor: not-allowed; }
    .tf-locked-badge { font-size: 10px; color: var(--gray-400); display: flex; align-items: center; gap: 4px; margin-top: 3px; }

    /* ── Repayment plan cards ── */
    .tf-schedule { display: flex; flex-direction: column; gap: 10px; margin: 16px 0; }
    .tf-installment { background: var(--tf-blue-lt); border: 1.5px solid var(--tf-blue-md); border-radius: 11px; padding: 14px 16px; }
    .tf-installment-head { font-size: 13px; font-weight: 800; color: var(--tf-navy); margin-bottom: 6px; display: flex; justify-content: space-between; align-items: center; }
    .tf-installment-due { font-size: 11px; color: var(--tf-blue); font-weight: 600; }
    .tf-installment-body { display: flex; gap: 16px; font-size: 12.5px; flex-wrap: wrap; }
    .tf-installment-body span { color: var(--gray-500); }
    .tf-installment-body strong { color: var(--gray-900); }
    .tf-installment-total { font-size: 13px; font-weight: 800; color: var(--tf-indigo); margin-left: auto; }

    /* ── Summary strips ── */
    .tf-summary-strip { background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 10px; padding: 14px 16px; margin-bottom: 14px; }
    .tf-sum-row { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid var(--gray-100); font-size: 13px; }
    .tf-sum-row:last-child { border-bottom: none; }
    .tf-sum-lbl { color: var(--gray-500); }
    .tf-sum-val { font-weight: 700; font-family: var(--mono); font-size: 12.5px; }
    .tf-total-row { display: flex; justify-content: space-between; align-items: center; padding: 14px 16px; background: linear-gradient(135deg, var(--tf-navy) 0%, var(--tf-indigo) 100%); border-radius: 10px; margin-bottom: 14px; }
    .tf-total-lbl { font-size: 14px; font-weight: 800; color: #fff; }
    .tf-total-val { font-size: 22px; font-weight: 800; color: #fff; font-family: var(--mono); }

    /* ── Down payment highlight ── */
    .tf-downpay-box { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; background: var(--tf-green-lt); border: 1.5px solid #a7f3d0; border-radius: 12px; margin-bottom: 14px; }
    .tf-downpay-label { font-size: 13px; font-weight: 700; color: var(--tf-green); }
    .tf-downpay-value { font-size: 22px; font-weight: 800; color: var(--tf-green); font-family: var(--mono); }
    .tf-downpay-sub { font-size: 11px; color: var(--tf-green); opacity: .8; }

    /* ── Payment option cards ── */
    .tf-pay-option { background: #fff; border: 2px solid var(--gray-200); border-radius: 12px; overflow: hidden; transition: border-color .2s; margin-bottom: 12px; }
    .tf-pay-option.active { border-color: var(--tf-blue); }
    .tf-pay-option-head { display: flex; align-items: center; gap: 12px; padding: 16px 18px; cursor: pointer; user-select: none; }
    .tf-pay-radio { width: 20px; height: 20px; border-radius: 50%; border: 2px solid var(--gray-300); display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all .2s; }
    .tf-pay-option.active .tf-pay-radio { border-color: var(--tf-blue); background: var(--tf-blue); }
    .tf-pay-radio-dot { width: 8px; height: 8px; border-radius: 50%; background: #fff; transform: scale(0); transition: transform .15s; }
    .tf-pay-option.active .tf-pay-radio-dot { transform: scale(1); }
    .tf-pay-option-icon { width: 44px; height: 44px; border-radius: 11px; font-size: 22px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .tf-pay-option-body { display: none; padding: 0 18px 18px; border-top: 1px solid var(--gray-100); }
    .tf-pay-option.active .tf-pay-option-body { display: block; }

    /* Bank accounts */
    .tf-bank-card { background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 10px; padding: 12px 14px; position: relative; margin-bottom: 10px; }
    .tf-bank-name { font-size: 11px; font-weight: 700; color: var(--gray-400); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 4px; }
    .tf-bank-acct { font-size: 18px; font-weight: 800; color: var(--tf-navy); font-family: var(--mono); letter-spacing: .06em; }
    .tf-bank-holder { font-size: 12px; color: var(--gray-500); margin-top: 2px; }
    .tf-copy-btn { position: absolute; top: 10px; right: 10px; padding: 4px 10px; border: 1.5px solid var(--gray-200); border-radius: 7px; background: #fff; font-size: 11px; font-weight: 700; color: var(--gray-500); cursor: pointer; transition: all .15s; font-family: var(--font); }
    .tf-copy-btn:hover { border-color: var(--tf-blue); color: var(--tf-blue); background: var(--tf-blue-lt); }

    /* Ref input */
    .tf-ref-input { width: 100%; height: 44px; padding: 0 14px; border: 1.5px solid var(--gray-200); border-radius: 9px; font-size: 14px; font-family: var(--font); outline: none; transition: border-color .15s; background: var(--gray-50); margin-bottom: 12px; }
    .tf-ref-input:focus { border-color: var(--tf-blue); background: #fff; }
    .tf-ref-label { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--gray-400); margin-bottom: 5px; display: block; }

    /* ── Buttons ── */
    .tf-btn-row { display: flex; gap: 10px; margin-top: 22px; flex-wrap: wrap; }
    .tf-btn-primary { height: 48px; padding: 0 28px; background: var(--tf-blue); color: #fff; border: none; border-radius: 11px; font-size: 14px; font-weight: 800; cursor: pointer; font-family: var(--font); transition: all .15s; display: inline-flex; align-items: center; gap: 8px; }
    .tf-btn-primary:hover { background: #1e40af; transform: translateY(-1px); }
    .tf-btn-secondary { height: 48px; padding: 0 28px; background: linear-gradient(135deg, var(--tf-green) 0%, #10b981 100%); color: #fff; border: none; border-radius: 11px; font-size: 14px; font-weight: 800; cursor: pointer; font-family: var(--font); transition: all .2s; box-shadow: 0 4px 16px rgba(5,150,105,.3); display: inline-flex; align-items: center; gap: 8px; }
    .tf-btn-secondary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(5,150,105,.4); }
    .tf-btn-ghost { height: 48px; padding: 0 22px; background: #fff; border: 1.5px solid var(--gray-200); border-radius: 11px; font-size: 13.5px; font-weight: 700; color: var(--gray-700); cursor: pointer; font-family: var(--font); display: inline-flex; align-items: center; gap: 8px; transition: all .15s; }
    .tf-btn-ghost:hover { background: var(--gray-50); border-color: var(--gray-400); }
    .tf-btn-pay { width: 100%; height: 52px; background: linear-gradient(135deg, var(--tf-green) 0%, #10b981 100%); color: #fff; border: none; border-radius: 11px; font-size: 15px; font-weight: 800; cursor: pointer; font-family: var(--font); transition: all .2s; box-shadow: 0 4px 16px rgba(5,150,105,.3); display: flex; align-items: center; justify-content: center; gap: 9px; }
    .tf-btn-pay:hover { transform: translateY(-1px); }
    .tf-btn-bank { width: 100%; height: 50px; background: var(--tf-navy); color: #fff; border: none; border-radius: 11px; font-size: 14px; font-weight: 800; cursor: pointer; font-family: var(--font); display: flex; align-items: center; justify-content: center; gap: 9px; transition: background .15s; }
    .tf-btn-bank:hover { background: #0f2460; }

    /* ── Notice ── */
    .tf-notice { display: flex; align-items: flex-start; gap: 9px; padding: 11px 14px; border-radius: 9px; font-size: 12.5px; margin-bottom: 16px; }
    .tf-notice.info  { background: var(--tf-blue-lt); color: var(--tf-blue); border: 1px solid var(--tf-blue-md); }
    .tf-notice.warn  { background: var(--tf-amber-lt); color: var(--tf-amber); border: 1px solid #fed7aa; }
    .tf-notice.green { background: var(--tf-green-lt); color: var(--tf-green); border: 1px solid #a7f3d0; }

    /* ── Right rail ── */
    .tf-rail-card { background: #fff; border: 1px solid var(--gray-200); border-radius: 14px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.07); position: sticky; top: 20px; }
    .tf-rail-head { padding: 14px 18px; background: linear-gradient(135deg, var(--tf-navy) 0%, var(--tf-indigo) 100%); }
    .tf-rail-title { font-size: 15px; font-weight: 800; color: #fff; }
    .tf-rail-sub   { font-size: 11px; color: rgba(255,255,255,.7); margin-top: 2px; }
    .tf-rail-body  { padding: 14px 18px; }
    .tf-rail-row   { display: flex; align-items: flex-start; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid var(--gray-100); font-size: 12.5px; gap: 10px; }
    .tf-rail-row:last-child { border-bottom: none; }
    .tf-rail-lbl { color: var(--gray-500); }
    .tf-rail-val { font-weight: 700; text-align: right; }
    .tf-rail-total { display: flex; justify-content: space-between; padding: 14px 18px; border-top: 2px solid var(--gray-200); }
    .tf-rail-total-lbl { font-size: 13px; font-weight: 800; color: var(--tf-navy); }
    .tf-rail-total-val { font-size: 20px; font-weight: 800; color: var(--tf-navy); font-family: var(--mono); }
    .tf-flex-logo  { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 800; color: var(--tf-indigo); padding: 10px 18px; border-top: 1px solid var(--gray-100); }

    @media (max-width: 880px) { .tf-grid { grid-template-columns: 1fr; } .tf-rail-card { position: static; } }
    @media (max-width: 580px) { .tf-outer { padding: 12px 10px 60px; } .tf-field-grid { grid-template-columns: 1fr; } .tf-hero-title { font-size: 18px; } .tf-installment-body { gap: 8px; } }
</style>

<div class="tf-outer">

    {{-- ── Hero Header ── --}}
    <div class="tf-hero">
        <div style="position:relative;z-index:2;">
            <div class="tf-hero-badge">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                TravelFlex Instalment Plan
            </div>
            <div class="tf-hero-title">Pay for your flight in easy instalments</div>
            <div class="tf-hero-sub">
                Secure your seat today with a 30% down payment. 
                Pay the balance over your chosen repayment period at a fixed 5% interest rate. 
                Provided by a licensed third-party lender.
            </div>
            <div class="tf-progress-wrap" id="tfProgressWrap">
                <div class="tf-progress-bar" id="tfProgress" style="width: {{ $eligible ? '20%' : '0%' }};"></div>
            </div>
        </div>
    </div>

    @if(!$eligible)
    {{-- ── Ineligibility Notice ── --}}
    <div class="tf-ineligible" style="margin-bottom: 22px;">
        <div class="tf-ineligible-icon">⏰</div>
        <div>
            <div style="font-size:16px;font-weight:800;color:#92400e;margin-bottom:6px;">TravelFlex Not Available for This Booking</div>
            <div style="font-size:13.5px;color:#78350f;line-height:1.65;">
                TravelFlex requires a minimum of <strong>14 days</strong> between today and the travel date.
                Your flight departs on <strong>{{ $departDateLabel }}</strong>
                @if($daysToDepart > 0) ({{ $daysToDepart }} day{{ $daysToDepart !== 1 ? 's' : '' }} away) @else — which has already passed or is today @endif.
                <br><br>
                Please choose a different payment method for this booking.
            </div>
            <a href="{{ route('flights.payment.options') }}" style="display:inline-flex;align-items:center;gap:7px;margin-top:14px;padding:10px 20px;background:var(--tf-amber);color:#fff;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Back to Payment Options
            </a>
        </div>
    </div>

    @else

    <div class="tf-grid" x-data="travelFlex()" x-init="init()">

        {{-- ── Main Steps Column ── --}}
        <div>

            {{-- ══ STEP 0: DISCLAIMER ══ --}}
            <div :class="{ 'tf-step active': true }" x-show="step === 0" x-transition>
                <div class="tf-card">
                    <div class="tf-step-label"><span>1</span> Legal Disclaimer &amp; Agreement</div>

                    <div class="tf-notice info">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span>Please read this agreement carefully before proceeding. You must agree to all terms to use TravelFlex.</span>
                    </div>

                    <div class="tf-disclaimer-box">
                        <h4>1. Nature of the Product</h4>
                        <p>TravelFlex is an instalment payment plan facilitated by Travelwheel Limited ("Travelwheel") in partnership with a licensed third-party financial institution ("the Lender"). By proceeding, you acknowledge that you are entering into a <strong>loan agreement</strong> with the Lender, not with Travelwheel. Travelwheel acts solely as a booking agent and facilitator.</p>

                        <h4>2. Loan Agreement</h4>
                        <p>The instalment plan constitutes a credit arrangement governed by the applicable consumer credit laws of the Federal Republic of Nigeria. The Lender will disburse the balance of your ticket cost to the airline on your behalf. You agree to repay the Lender the principal amount plus interest on the schedule agreed herein.</p>

                        <h4>3. Down Payment & Non-Refundability</h4>
                        <p>A minimum down payment of <strong>30%</strong> of the total ticket cost is required to initiate the plan. This down payment is <strong>non-refundable</strong> once the booking is confirmed, except where the airline cancels the flight. The down payment is used to secure your seat reservation with the airline.</p>

                        <h4>4. Interest Rate</h4>
                        <p>A fixed interest rate of <strong>5% per repayment interval</strong> is applied to the outstanding balance. This rate is applied per instalment period (e.g., monthly or weekly), not per annum. You agree that the total cost of credit (including interest) will be disclosed to you before you confirm the plan.</p>

                        <h4>5. Repayment Obligations</h4>
                        <p>You agree to make payments on or before the due dates specified in your repayment schedule. Late or missed payments may result in (a) cancellation of your flight booking, (b) forfeiture of amounts paid, (c) reporting to credit bureaus, and (d) legal action by the Lender to recover outstanding amounts.</p>

                        <h4>6. Flight Booking & Ticketing</h4>
                        <p>Your flight will be booked and held by Travelwheel upon receipt of the down payment. The e-ticket will be issued only after the down payment is confirmed. If you fail to complete subsequent instalments, Travelwheel reserves the right to cancel the booking and notify the Lender.</p>

                        <h4>7. Eligibility Requirements</h4>
                        <p>To qualify for TravelFlex, you must (a) be at least 18 years of age, (b) be a Nigerian resident with a valid BVN and NIN, (c) apply at least 14 days before your travel date, and (d) pass any credit checks conducted by the Lender. Travelwheel does not guarantee approval of the loan.</p>

                        <h4>8. Privacy & Data Sharing</h4>
                        <p>By proceeding, you consent to Travelwheel sharing your personal information (including name, contact details, passport information, BVN, and NIN) with the Lender solely for the purposes of this credit arrangement. Your data will be handled in accordance with the Nigeria Data Protection Regulation (NDPR).</p>

                        <h4>9. Governing Law</h4>
                        <p>This agreement is governed by the laws of the Federal Republic of Nigeria. Any disputes shall be subject to the exclusive jurisdiction of Nigerian courts.</p>

                        <h4>10. Acknowledgement</h4>
                        <p>By ticking the checkbox below and clicking "I Agree & Continue", you confirm that you have read, understood, and agree to be bound by all the terms above. You further confirm that you are entering this agreement voluntarily and of your own free will.</p>
                    </div>

                    <div class="tf-agree-row" @click="toggleAgree()">
                        <input type="checkbox" id="tfAgree" :checked="agreed" @click.stop="toggleAgree()">
                        <label for="tfAgree" @click.prevent>
                            I have read and agree to the TravelFlex Terms &amp; Conditions, including the loan agreement with the third-party Lender, the interest rate of 5% per repayment interval, and the non-refundable 30% down payment policy.
                        </label>
                    </div>

                    <div class="tf-btn-row">
                        <a href="{{ route('flights.payment.options') }}" class="tf-btn-ghost">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                            Back
                        </a>
                        <button type="button" class="tf-btn-primary" @click="agreeAndProceed()" :disabled="!agreed" :style="!agreed ? 'opacity:.45;cursor:not-allowed' : ''">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                            I Agree &amp; Continue
                        </button>
                    </div>
                </div>
            </div>

            {{-- ══ STEP 1: CALCULATOR ══ --}}
            <div x-show="step === 1" x-transition>
                <div class="tf-card">
                    <div class="tf-step-label"><span>2</span> Payment Calculator</div>

                    <div class="tf-field-grid">
                        {{-- Travel Date — prefilled & locked --}}
                        <div class="tf-field">
                            <div class="tf-label">Travel Date</div>
                            <input class="tf-input" type="text" value="{{ $departDateLabel }}" readonly>
                            <div class="tf-locked-badge">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                Auto-filled from booking
                            </div>
                        </div>

                        {{-- Ticket Cost — prefilled & locked --}}
                        <div class="tf-field">
                            <div class="tf-label">Ticket Cost</div>
                            <input class="tf-input" type="text" :value="formatCurrency(ticketCost)" readonly>
                            <div class="tf-locked-badge">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                Fixed from your booking
                            </div>
                        </div>

                        {{-- Down Payment % — pre-selected 30%, changeable --}}
                        <div class="tf-field">
                            <div class="tf-label">Down Payment % <span class="tf-req">*</span></div>
                            <select class="tf-select" x-model="downPercent" @change="onDownPercentChange()">
                                <option value="30">30% (Minimum)</option>
                                <option value="40">40%</option>
                                <option value="50">50%</option>
                                <option value="60">60%</option>
                                <option value="70">70%</option>
                                <option value="80">80%</option>
                                <option value="90">90%</option>
                            </select>
                        </div>

                        {{-- Down Payment Amount — computed --}}
                        <div class="tf-field">
                            <div class="tf-label">Down Payment Amount</div>
                            <input class="tf-input" type="text" :value="formatCurrency(downPaymentAmount)" readonly>
                            <div class="tf-locked-badge">Calculated automatically</div>
                        </div>

                        {{-- Repayment Plan --}}
                        <div class="tf-field tf-field-full">
                            <div class="tf-label">Repayment Plan <span class="tf-req">*</span></div>
                            <select class="tf-select" x-model="repaymentPlan" @change="onPlanChange()" :disabled="repaymentOptions.length === 0">
                                <option value="">Select a repayment plan</option>
                                <template x-for="opt in repaymentOptions" :key="opt.value">
                                    <option :value="opt.value" x-text="opt.label"></option>
                                </template>
                            </select>
                            <div class="tf-locked-badge" x-show="repaymentOptions.length > 0">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                                <span x-text="repaymentOptions.length + ' option(s) available based on your travel date'"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Live preview before calculating --}}
                    <div x-show="repaymentPlan && !calculated" class="tf-notice info">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span>Click <strong>Calculate Plan</strong> to see your full instalment schedule.</span>
                    </div>

                    {{-- Schedule results --}}
                    <div x-show="calculated" x-transition>
                        <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--gray-400);margin-bottom:10px;">Repayment Schedule</div>
                        <div class="tf-schedule">
                            <template x-for="(inst, i) in schedule" :key="i">
                                <div class="tf-installment">
                                    <div class="tf-installment-head">
                                        <span x-text="inst.label + ' — ' + inst.dueDate"></span>
                                        <span class="tf-installment-total" x-text="formatCurrency(inst.total)"></span>
                                    </div>
                                    <div class="tf-installment-body">
                                        <span>Principal: <strong x-text="formatCurrency(inst.principal)"></strong></span>
                                        <span>Interest: <strong x-text="formatCurrency(inst.interest)"></strong></span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="tf-summary-strip">
                            <div class="tf-sum-row"><span class="tf-sum-lbl">Ticket Cost</span><span class="tf-sum-val" x-text="formatCurrency(ticketCost)"></span></div>
                            <div class="tf-sum-row"><span class="tf-sum-lbl">Down Payment (<span x-text="downPercent"></span>%)</span><span class="tf-sum-val" x-text="formatCurrency(downPaymentAmount)"></span></div>
                            <div class="tf-sum-row"><span class="tf-sum-lbl">Remaining Balance</span><span class="tf-sum-val" x-text="formatCurrency(remainingBalance)"></span></div>
                            <div class="tf-sum-row"><span class="tf-sum-lbl">Total Interest (5%/period)</span><span class="tf-sum-val" x-text="formatCurrency(totalInterest)"></span></div>
                        </div>
                        <div class="tf-total-row">
                            <span class="tf-total-lbl">Total Payable (All Instalments + Down)</span>
                            <span class="tf-total-val" x-text="formatCurrency(grandTotal)"></span>
                        </div>
                    </div>

                    <div class="tf-btn-row">
                        <button type="button" class="tf-btn-ghost" @click="step = 0; setProgress(20)">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                            Back
                        </button>
                        <button type="button" class="tf-btn-primary" @click="calculate()" x-show="!calculated || repaymentPlan !== lastPlan">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect x="2" y="2" width="20" height="20" rx="3"/><line x1="8" y1="10" x2="16" y2="10"/><line x1="8" y1="14" x2="16" y2="14"/></svg>
                            Calculate Plan
                        </button>
                        <button type="button" class="tf-btn-secondary" x-show="calculated && repaymentPlan === lastPlan" @click="proceedToPayment()">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            Proceed to Pay Down
                        </button>
                    </div>
                </div>
            </div>

            {{-- ══ STEP 2: PAY DOWN PAYMENT ══ --}}
            <div x-show="step === 2" x-transition>
                <div class="tf-card">
                    <div class="tf-step-label"><span>3</span> Pay Down Payment</div>

                    <div class="tf-downpay-box">
                        <div>
                            <div class="tf-downpay-label">Down Payment Due Now</div>
                            <div class="tf-downpay-sub">Required to secure your booking</div>
                        </div>
                        <div>
                            <div class="tf-downpay-value" x-text="formatCurrency(downPaymentAmount)"></div>
                            <div style="font-size:11px;color:var(--tf-green);text-align:right;" x-text="downPercent + '% of ' + formatCurrency(ticketCost)"></div>
                        </div>
                    </div>

                    <div class="tf-notice warn">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span>This down payment is <strong>non-refundable</strong> once confirmed. Your flight will be booked and held immediately after payment.</span>
                    </div>

                    {{-- Plan recap --}}
                    <div class="tf-summary-strip" style="margin-bottom:20px;">
                        <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--gray-400);margin-bottom:8px;">Plan Summary</div>
                        <div class="tf-sum-row"><span class="tf-sum-lbl">Flight</span><span class="tf-sum-val" style="font-family:var(--font)">{{ ($firstSeg['from']??'') }} → {{ ($lastSeg['to']??'') }}</span></div>
                        <div class="tf-sum-row"><span class="tf-sum-lbl">Travel Date</span><span class="tf-sum-val" style="font-family:var(--font)">{{ $departDateLabel }}</span></div>
                        <div class="tf-sum-row"><span class="tf-sum-lbl">Repayment Plan</span><span class="tf-sum-val" style="font-family:var(--font)" x-text="repaymentPlan"></span></div>
                        <div class="tf-sum-row"><span class="tf-sum-lbl">Instalments</span><span class="tf-sum-val" style="font-family:var(--font)" x-text="schedule.length + ' payment(s)'"></span></div>
                        <div class="tf-sum-row"><span class="tf-sum-lbl">Total Payable</span><span class="tf-sum-val" x-text="formatCurrency(grandTotal)"></span></div>
                    </div>

                    {{-- ── Payment Option 1: Bank Transfer ── --}}
                    <div class="tf-pay-option" :class="{ active: payOption === 'bank' }" @click="payOption = 'bank'">
                        <div class="tf-pay-option-head">
                            <div class="tf-pay-radio"><div class="tf-pay-radio-dot"></div></div>
                            <div class="tf-pay-option-icon" style="background:#e0f2fe;">🏦</div>
                            <div>
                                <div style="font-size:14px;font-weight:800;color:var(--gray-900);">Direct Bank Transfer</div>
                                <div style="font-size:12px;color:var(--gray-500);margin-top:2px;">Transfer down payment to our account</div>
                            </div>
                            <span style="margin-left:auto;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;background:var(--tf-amber-lt);color:var(--tf-amber);flex-shrink:0;">Manual</span>
                        </div>
                        <div class="tf-pay-option-body">
                            <div class="tf-notice warn" style="margin-top:12px">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                <span>Transfer exactly <strong x-text="formatCurrency(downPaymentAmount)"></strong>. Your plan will be activated once payment is verified (2–4 hrs).</span>
                            </div>
                            @foreach($bankAccounts as $acct)
                            <div class="tf-bank-card">
                                <div class="tf-bank-name">{{ $acct['bank'] }}</div>
                                <div class="tf-bank-acct">{{ $acct['account_number'] }}</div>
                                <div class="tf-bank-holder">{{ $acct['account_name'] }}</div>
                                <button type="button" class="tf-copy-btn"
                                    onclick="navigator.clipboard.writeText('{{ $acct['account_number'] }}').then(()=>{this.textContent='Copied!';setTimeout(()=>this.textContent='Copy',1500)})">
                                    Copy
                                </button>
                            </div>
                            @endforeach
                            <form method="POST" action="{{ route('flights.travelflex.application') }}" id="tf-bank-form">
                                @csrf
                                <!-- <input type="hidden" name="pay_method" value="bank_transfer">
                                <input type="hidden" name="down_payment" :value="downPaymentAmount" x-bind:value="downPaymentAmount">
                                <input type="hidden" name="down_percent" :value="downPercent" x-bind:value="downPercent">
                                <input type="hidden" name="repayment_plan" :value="repaymentPlan" x-bind:value="repaymentPlan">
                                <input type="hidden" name="grand_total" :value="grandTotal" x-bind:value="grandTotal">
                                <input type="hidden" name="total_interest" :value="totalInterest" x-bind:value="totalInterest">
                                <input type="hidden" name="schedule_json" :value="JSON.stringify(schedule)" x-bind:value="JSON.stringify(schedule)">
                                <label class="tf-ref-label" style="margin-top:14px;">Your Payment Reference (optional)</label>
                                <input class="tf-ref-input" type="text" name="payment_reference" placeholder="e.g. bank transaction ref or your name"> -->
                                <button type="submit" class="tf-btn-bank">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    I Have Made Payment
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- ── Payment Option 2: Gateway ── --}}
                    <div class="tf-pay-option" :class="{ active: payOption === 'gateway' }" @click="payOption = 'gateway'">
                        <div class="tf-pay-option-head">
                            <div class="tf-pay-radio"><div class="tf-pay-radio-dot"></div></div>
                            <div class="tf-pay-option-icon" style="background:#f0fdf4;">💳</div>
                            <div>
                                <div style="font-size:14px;font-weight:800;color:var(--gray-900);">Pay Online</div>
                                <div style="font-size:12px;color:var(--gray-500);margin-top:2px;">Card, bank transfer or USSD — instant confirmation</div>
                            </div>
                            <span style="margin-left:auto;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;background:var(--tf-green-lt);color:var(--tf-green);flex-shrink:0;">Instant</span>
                        </div>
                        <div class="tf-pay-option-body">
                            <div class="tf-notice green" style="margin-top:12px">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                                <span>Payment is processed securely. Your booking will be confirmed and plan activated <strong>immediately</strong>.</span>
                            </div>
                            <form method="POST" action="{{ route('flights.travelflex.application') }}" id="tf-gw-form" style="margin-top:12px;">
                                @csrf
                                <input type="hidden" name="pay_method" value="gateway">
                                <!-- <input type="hidden" name="down_payment" :value="downPaymentAmount" x-bind:value="downPaymentAmount">
                                <input type="hidden" name="down_percent" :value="downPercent" x-bind:value="downPercent">
                                <input type="hidden" name="repayment_plan" :value="repaymentPlan" x-bind:value="repaymentPlan">
                                <input type="hidden" name="grand_total" :value="grandTotal" x-bind:value="grandTotal">
                                <input type="hidden" name="total_interest" :value="totalInterest" x-bind:value="totalInterest">
                                <input type="hidden" name="schedule_json" :value="JSON.stringify(schedule)" x-bind:value="JSON.stringify(schedule)"> -->
                                <button type="submit" class="tf-btn-pay" id="tf-gw-btn">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                    Pay <span x-text="formatCurrency(downPaymentAmount)" style="margin:0 4px;"></span> Down Payment Now
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="tf-btn-row" style="margin-top:10px;">
                        <button type="button" class="tf-btn-ghost" @click="step = 1; setProgress(40)">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                            Back to Calculator
                        </button>
                    </div>
                </div>
            </div>

        </div>{{-- /tf-main --}}

        {{-- ── Right Rail ── --}}
        <aside>
            <div class="tf-rail-card">
                <div class="tf-rail-head">
                    <div class="tf-rail-title">📆 TravelFlex</div>
                    <div class="tf-rail-sub">Your instalment plan summary</div>
                </div>
                <div class="tf-rail-body">
                    <div class="tf-rail-row">
                        <span class="tf-rail-lbl">Flight</span>
                        <span class="tf-rail-val" style="font-size:13px;font-weight:800;">{{ ($firstSeg['from']??'') }} → {{ ($lastSeg['to']??'') }}</span>
                    </div>
                    <div class="tf-rail-row">
                        <span class="tf-rail-lbl">Travel Date</span>
                        <span class="tf-rail-val" style="font-size:12px">{{ $departDateLabel }}</span>
                    </div>
                    <div class="tf-rail-row">
                        <span class="tf-rail-lbl">Airline</span>
                        <span class="tf-rail-val">{{ $airline }}</span>
                    </div>
                    <div class="tf-rail-row">
                        <span class="tf-rail-lbl">Cabin</span>
                        <span class="tf-rail-val">{{ $cabin }}</span>
                    </div>
                    <div class="tf-rail-row">
                        <span class="tf-rail-lbl">Ticket Cost</span>
                        <span class="tf-rail-val" style="font-family:var(--mono)">{{ $sym }}{{ number_format($totalPrice, 2) }}</span>
                    </div>
                    <div class="tf-rail-row" x-show="calculated">
                        <span class="tf-rail-lbl">Down Payment</span>
                        <span class="tf-rail-val" style="font-family:var(--mono);color:var(--tf-green)" x-text="formatCurrency(downPaymentAmount)"></span>
                    </div>
                    <div class="tf-rail-row" x-show="calculated">
                        <span class="tf-rail-lbl">Repayment</span>
                        <span class="tf-rail-val" x-text="repaymentPlan || '—'"></span>
                    </div>
                    <div class="tf-rail-row" x-show="calculated">
                        <span class="tf-rail-lbl">Interest</span>
                        <span class="tf-rail-val" style="font-family:var(--mono)" x-text="formatCurrency(totalInterest)"></span>
                    </div>
                </div>

                <div class="tf-rail-total" x-show="calculated">
                    <span class="tf-rail-total-lbl">Total Payable</span>
                    <span class="tf-rail-total-val" x-text="formatCurrency(grandTotal)"></span>
                </div>

                <div class="tf-flex-logo">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Secured by TravelFlex &amp; Travelwheel
                </div>
            </div>

            {{-- Eligibility info --}}
            <div style="background:#fff;border:1px solid var(--gray-200);border-radius:12px;padding:16px 18px;margin-top:14px;box-shadow:0 1px 4px rgba(0,0,0,.06)">
                <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--gray-400);margin-bottom:10px;">Eligibility</div>
                <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;margin-bottom:8px;color:var(--tf-green)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                    Travel date ≥ 14 days away ({{ $daysToDepart }} days)
                </div>
                <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;margin-bottom:8px;color:var(--tf-green)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                    Minimum 30% down payment
                </div>
                <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--gray-400)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    5% interest per repayment interval
                </div>
            </div>
        </aside>

    </div>{{-- /tf-grid --}}
    @endif

</div>

<script src="//unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function travelFlex() {
    return {
        // ── State ────────────────────────────────────────────────────────────
        step:              0,
        agreed:            false,
        ticketCost:        {{ $totalPrice }},
        travelDateISO:     '{{ $departDateISO }}',
        daysToDepart:      {{ $daysToDepart }},
        sym:               '{{ $sym }}',

        downPercent:       30,
        downPaymentAmount: 0,
        remainingBalance:  0,
        repaymentPlan:     '',
        lastPlan:          '',
        repaymentOptions:  [],
        schedule:          [],
        totalInterest:     0,
        grandTotal:        0,
        calculated:        false,
        payOption:         '',

        // ── Init ─────────────────────────────────────────────────────────────
        init() {
            this.buildRepaymentOptions();
            this.onDownPercentChange();
        },

        // ── Helpers ───────────────────────────────────────────────────────────
        formatCurrency(val) {
            return this.sym + Number(val).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        setProgress(pct) {
            document.getElementById('tfProgress').style.width = pct + '%';
        },

        toggleAgree() { this.agreed = !this.agreed; },

        agreeAndProceed() {
            if (!this.agreed) return;
            this.step = 1;
            this.setProgress(40);
        },

        // ── Build repayment options based on days to departure ────────────────
        // Options available if (daysToDepart - 14) >= option.days
        buildRepaymentOptions() {
            const safedays = Math.max(0, this.daysToDepart - 14); // subtract the 14-day buffer
            const allOptions = [
                { label: '24 hours',  value: '24 hours',  days: 1   },
                { label: '48 hours',  value: '48 hours',  days: 2   },
                { label: '72 hours',  value: '72 hours',  days: 3   },
                { label: '1 week',    value: '1 week',    days: 7   },
                { label: '2 weeks',   value: '2 weeks',   days: 14  },
                { label: '3 weeks',   value: '3 weeks',   days: 21  },
                { label: '1 month',   value: '1 month',   days: 30  },
                { label: '2 months',  value: '2 months',  days: 60  },
                { label: '3 months',  value: '3 months',  days: 90  },
                { label: '4 months',  value: '4 months',  days: 120 },
                { label: '5 months',  value: '5 months',  days: 150 },
            ];
            this.repaymentOptions = allOptions.filter(o => safedays >= o.days);
        },

        onDownPercentChange() {
            this.downPaymentAmount = this.ticketCost * (this.downPercent / 100);
            this.remainingBalance  = this.ticketCost - this.downPaymentAmount;
            this.calculated = false; // force recalculate
        },

        onPlanChange() {
            this.calculated = false;
        },

        // ── Parse repayment label into interval days and instalment count ──────
        parseRepaymentLabel(label) {
            label = (label || '').toString().trim().toLowerCase();
            let unitDays = 30, count = 1;
            let m = label.match(/(\d+)\s*month/);
            if (m) { count = parseInt(m[1], 10); unitDays = 30; return { count, unitDays }; }
            m = label.match(/(\d+)\s*week/);
            if (m) { count = parseInt(m[1], 10); unitDays = 7; return { count, unitDays }; }
            m = label.match(/(\d+)\s*hour/);
            if (m) { const hrs = parseInt(m[1], 10); unitDays = Math.max(1, Math.ceil(hrs / 24)); count = 1; return { count, unitDays }; }
            if (/month/.test(label)) return { count: 1, unitDays: 30 };
            if (/week/.test(label))  return { count: 1, unitDays: 7  };
            if (/hour/.test(label))  return { count: 1, unitDays: 1  };
            return { count: 1, unitDays: 30 };
        },

        // ── Main calculation ──────────────────────────────────────────────────
        calculate() {
            if (!this.repaymentPlan) return alert('Please select a repayment plan.');

            this.onDownPercentChange();

            const parsed      = this.parseRepaymentLabel(this.repaymentPlan);
            const intervalDays= parsed.unitDays;
            const numPeriods  = parsed.count;
            const RATE        = 0.05; // 5% per interval

            // Instalment schedule proportions
            const proportions = {
                1: [1.0],
                2: [0.5, 0.5],
                3: [0.4, 0.3, 0.3],
                4: [0.25, 0.25, 0.25, 0.25],
                5: [0.2, 0.2, 0.2, 0.2, 0.2],
            };
            const schedule = proportions[numPeriods] || [1.0];

            const ordinals = ['1st', '2nd', '3rd', '4th', '5th'];
            let   dueDate  = new Date();
            dueDate.setDate(dueDate.getDate() + intervalDays);

            let totalInterest = 0;
            this.schedule = schedule.map((portion, i) => {
                if (i > 0) dueDate.setDate(dueDate.getDate() + intervalDays);
                const interest  = this.remainingBalance * RATE;
                const principal = portion * this.remainingBalance;
                const total     = principal + interest;
                totalInterest  += interest;
                return {
                    label:     (ordinals[i] || (i+1)+'th') + ' Payment',
                    dueDate:   dueDate.toDateString(),
                    principal: Math.round(principal * 100) / 100,
                    interest:  Math.round(interest  * 100) / 100,
                    total:     Math.round(total      * 100) / 100,
                };
            });

            this.totalInterest = Math.round(totalInterest * 100) / 100;
            this.grandTotal    = Math.round((this.ticketCost + this.totalInterest) * 100) / 100;
            this.lastPlan      = this.repaymentPlan;
            this.calculated    = true;
            this.setProgress(60);
        },

        proceedToPayment() {
            if (!this.calculated) return alert('Please calculate first.');
            this.step = 2;
            this.setProgress(80);
        },
    };
}

// Prevent double-submit on gateway form
document.addEventListener('DOMContentLoaded', function () {
    const gwForm = document.getElementById('tf-gw-form');
    if (gwForm) {
        gwForm.addEventListener('submit', function () {
            const btn = document.getElementById('tf-gw-btn');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Processing Payment…';
            }
        });
    }
});
</script>
@endcomponent