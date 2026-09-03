{{-- resources/views/livewire/pages/flight/flight-payment-options.blade.php --}}
@component('layouts.app', ['title' => 'Choose Payment Method'])

@php
    $flight = $flight ?? [];
    $currency = $flight['currency'] ?? 'NGN';
    $sym = match ($currency) {
        'NGN' => '₦',
        'USD' => '$',
        'GBP' => '£',
        'EUR' => '€',
        default => $currency . ' ',
    };
    $fmt = fn ($value) => $sym . number_format((float) $value, 2);

    $selectedExtras = $selectedExtras ?? session('selectedExtras', []);
    $extrasTotal = (float) ($extrasTotal ?? 0);
    $baseTotal = (float) ($flight['price'] ?? 0);
    $total = $baseTotal + $extrasTotal;

    $segments = $flight['segments'] ?? [];
    $returnSegments = $flight['returnSegments'] ?? [];
    $multiLegs = $flight['multiLegs'] ?? [];
    $firstSeg = $segments[0] ?? [];
    $lastSeg = ! empty($segments) ? $segments[count($segments) - 1] : [];
    $isReturn = count($returnSegments) > 0;
    $isMulti = count($multiLegs) > 0;
    $tripLabel = $isReturn ? 'Round trip' : ($isMulti ? 'Multi-city' : 'One-way');
    $routeLabel = trim(($firstSeg['from'] ?? '') . ' → ' . ($lastSeg['to'] ?? ''), ' →');
    $cabinLabel = \App\Support\FlightDisplay::cabin($flight);
    $fareType = $flight['fareType'] ?? 'Public';
    $breakdown = $flight['fareBreakdown'] ?? [];
    $isRefundable = (bool) ($flight['isRefundable'] ?? false);

    $departDT = $segments[0]['departDT'] ?? null;
    $daysToDepart = $departDT ? max(0, (int) now()->diffInDays(\Carbon\Carbon::parse($departDT), false)) : 0;
    $flexEligible = $daysToDepart >= 14 && $isRefundable;

    $tktFormatted = '';
    $tktHours = 0;
    if (! empty($tktTimeLimit)) {
        try {
            $tktDt = \Carbon\Carbon::parse($tktTimeLimit);
            $tktFormatted = $tktDt->timezone('Africa/Lagos')->format('D, d M Y, H:i');
            $tktHours = max(0, (int) now()->diffInHours($tktDt, false));
        } catch (\Throwable) {
            $tktFormatted = '';
        }
    }

    $bankAccounts = config('travelwheel.travelflex_bank_accounts', []);
@endphp

<style>
    :root {
        --tw-blue: #303191;
        --tw-blue-700: #252675;
        --tw-green: #009933;
        --tw-surface: #ffffff;
        --tw-soft: #f8f9fc;
        --tw-line: #e6e8ee;
        --tw-line-2: #f2f4f7;
        --tw-text: #111827;
        --tw-muted: #667085;
        --tw-faint: #98a2b3;
        --tw-red: #dc2626;
        --tw-amber: #d97706;
        --tw-shadow: 0 18px 48px rgba(16, 24, 40, .08);
        --tw-font: 'Open Sans', 'Plus Jakarta Sans', Arial, sans-serif;
        --tw-mono: 'DM Mono', Consolas, monospace;
    }

    body {
        background: linear-gradient(180deg, #fff 0%, var(--tw-soft) 44%, #fff 100%);
        color: var(--tw-text);
        font-family: var(--tw-font);
        margin-top: 112px;
    }

    .pay-wrap {
        max-width: 1216px;
        margin: 0 auto;
        padding: 24px 16px 76px;
    }

    .pay-crumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 7px;
        margin-bottom: 16px;
        color: var(--tw-faint);
        font-size: 12px;
    }

    .pay-crumb a {
        color: var(--tw-blue);
        font-weight: 700;
        text-decoration: none;
    }

    .pay-hero {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 18px;
        align-items: center;
        padding: 20px;
        margin-bottom: 18px;
        border: 1px solid rgba(48, 49, 145, .12);
        border-radius: 16px;
        background: var(--tw-surface);
        box-shadow: var(--tw-shadow);
    }

    .pay-kicker {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-height: 26px;
        padding: 4px 10px;
        margin-bottom: 10px;
        border-radius: 999px;
        background: #eef2ff;
        color: var(--tw-blue);
        font-size: 11px;
        font-weight: 850;
    }

    .pay-title {
        margin: 0;
        color: var(--tw-text);
        font-size: clamp(24px, 3vw, 34px);
        font-weight: 900;
        line-height: 1.1;
        letter-spacing: 0;
    }

    .pay-subtitle {
        max-width: 680px;
        margin-top: 8px;
        color: var(--tw-muted);
        font-size: 13.5px;
        line-height: 1.6;
    }

    .pay-deadline {
        min-width: 260px;
        padding: 14px;
        border: 1px solid #fed7aa;
        border-radius: 14px;
        background: #fff7ed;
        color: #9a3412;
    }

    .pay-deadline-label {
        font-size: 10.5px;
        font-weight: 850;
        letter-spacing: .05em;
        text-transform: uppercase;
    }

    .pay-deadline-time {
        margin-top: 5px;
        font-size: 13px;
        font-weight: 850;
        line-height: 1.35;
    }

    .pay-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 342px;
        gap: 18px;
        align-items: start;
    }

    .pay-main,
    .pay-rail {
        min-width: 0;
    }

    .pay-options {
        display: grid;
        gap: 12px;
    }

    .pay-option {
        position: relative;
        border: 1px solid var(--tw-line);
        border-radius: 16px;
        background: var(--tw-surface);
        box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
        overflow: hidden;
        transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
    }

    .pay-option:hover {
        border-color: rgba(48, 49, 145, .25);
        box-shadow: 0 14px 36px rgba(16, 24, 40, .08);
        transform: translateY(-1px);
    }

    .pay-option.is-active {
        border-color: rgba(48, 49, 145, .45);
        box-shadow: 0 18px 48px rgba(48, 49, 145, .12);
    }

    .pay-option-head {
        display: grid;
        grid-template-columns: 44px minmax(0, 1fr) auto;
        gap: 14px;
        align-items: center;
        width: 100%;
        padding: 18px;
        border: 0;
        background: transparent;
        color: inherit;
        cursor: pointer;
        font: inherit;
        text-align: left;
    }

    .pay-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: #f1f1ff;
        color: var(--tw-blue);
    }

    .pay-option-title {
        color: var(--tw-text);
        font-size: 15px;
        font-weight: 900;
        line-height: 1.25;
    }

    .pay-option-sub {
        margin-top: 3px;
        color: var(--tw-muted);
        font-size: 12.5px;
        line-height: 1.45;
    }

    .pay-badge {
        display: inline-flex;
        align-items: center;
        min-height: 24px;
        padding: 3px 10px;
        border-radius: 999px;
        background: #ecfdf3;
        color: var(--tw-green);
        font-size: 10.5px;
        font-weight: 850;
        white-space: nowrap;
    }

    .pay-badge.manual {
        background: #fff7ed;
        color: var(--tw-amber);
    }

    .pay-badge.disabled {
        background: var(--tw-line-2);
        color: var(--tw-faint);
    }

    .pay-option-body {
        display: none;
        padding: 0 18px 18px;
        border-top: 1px solid var(--tw-line-2);
    }

    .pay-option.is-active .pay-option-body {
        display: block;
        animation: payReveal .18s ease-out;
    }

    @keyframes payReveal {
        from { opacity: 0; transform: translateY(-4px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .pay-note {
        display: flex;
        gap: 9px;
        align-items: flex-start;
        margin: 14px 0;
        padding: 11px 12px;
        border: 1px solid #d7d8ff;
        border-radius: 12px;
        background: #f7f7ff;
        color: var(--tw-blue);
        font-size: 12.5px;
        line-height: 1.45;
    }

    .pay-note.warn {
        border-color: #fed7aa;
        background: #fff7ed;
        color: #9a3412;
    }

    .pay-note.danger {
        border-color: #fecaca;
        background: #fef2f2;
        color: var(--tw-red);
    }

    .pay-bank-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 14px;
    }

    .pay-bank {
        position: relative;
        min-width: 0;
        padding: 13px;
        border: 1px solid var(--tw-line);
        border-radius: 13px;
        background: #fbfcfe;
    }

    .pay-bank-name {
        color: var(--tw-muted);
        font-size: 11px;
        font-weight: 850;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .pay-bank-number {
        margin-top: 7px;
        color: var(--tw-text);
        font-family: var(--tw-mono);
        font-size: 16px;
        font-weight: 850;
        letter-spacing: .03em;
    }

    .pay-bank-holder {
        margin-top: 3px;
        color: var(--tw-muted);
        font-size: 11.5px;
    }

    .pay-copy {
        margin-top: 10px;
        height: 32px;
        padding: 0 12px;
        border: 1px solid var(--tw-line);
        border-radius: 8px;
        background: #fff;
        color: var(--tw-blue);
        cursor: pointer;
        font-size: 11.5px;
        font-weight: 850;
    }

    .pay-field {
        display: grid;
        gap: 6px;
        margin-bottom: 12px;
    }

    .pay-label {
        color: var(--tw-muted);
        font-size: 10.5px;
        font-weight: 850;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .pay-input {
        width: 100%;
        height: 44px;
        padding: 0 13px;
        border: 1px solid var(--tw-line);
        border-radius: 10px;
        background: #fbfcfe;
        color: var(--tw-text);
        font: inherit;
        outline: none;
        transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
    }

    .pay-input:focus {
        border-color: var(--tw-blue);
        background: #fff;
        box-shadow: 0 0 0 3px rgba(48, 49, 145, .10);
    }

    .pay-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        min-height: 50px;
        padding: 0 18px;
        border: 0;
        border-radius: 12px;
        background: var(--tw-blue);
        color: #fff;
        cursor: pointer;
        font: inherit;
        font-size: 14px;
        font-weight: 900;
        text-decoration: none;
        box-shadow: 0 14px 28px rgba(48, 49, 145, .18);
        transition: transform .18s ease, background .18s ease, box-shadow .18s ease;
    }

    .pay-action:hover {
        background: var(--tw-blue-700);
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 18px 36px rgba(48, 49, 145, .24);
    }

    .pay-action.secondary {
        background: #fff;
        color: var(--tw-blue);
        border: 1px solid rgba(48, 49, 145, .22);
        box-shadow: none;
    }

    .pay-action[disabled],
    .pay-action.disabled {
        background: var(--tw-line-2);
        color: var(--tw-faint);
        cursor: not-allowed;
        box-shadow: none;
        transform: none;
    }

    .pay-rail {
        position: sticky;
        top: 18px;
    }

    .pay-summary {
        overflow: hidden;
        border: 1px solid rgba(48, 49, 145, .12);
        border-radius: 16px;
        background: #fff;
        box-shadow: var(--tw-shadow);
    }

    .pay-summary-head {
        padding: 18px;
        border-bottom: 1px solid var(--tw-line);
        background: linear-gradient(180deg, #fff 0%, #fbfcff 100%);
    }

    .pay-summary-title {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--tw-text);
        font-size: 15px;
        font-weight: 900;
    }

    .pay-summary-sub {
        margin-top: 4px;
        color: var(--tw-muted);
        font-size: 11.5px;
        line-height: 1.35;
    }

    .pay-summary-body {
        padding: 16px 18px 8px;
    }

    .pay-route {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        padding-bottom: 14px;
        margin-bottom: 12px;
        border-bottom: 1px solid var(--tw-line-2);
    }

    .pay-route-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 999px;
        background: #f1f1ff;
        color: var(--tw-blue);
        flex: 0 0 30px;
    }

    .pay-route-title {
        color: var(--tw-text);
        font-size: 13.5px;
        font-weight: 900;
        line-height: 1.3;
    }

    .pay-route-meta {
        margin-top: 2px;
        color: var(--tw-muted);
        font-size: 11.5px;
    }

    .pay-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 7px 0;
        color: var(--tw-muted);
        font-size: 12.5px;
    }

    .pay-row strong {
        color: var(--tw-text);
        font-weight: 850;
        text-align: right;
    }

    .pay-total {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin: 12px 14px 14px;
        padding: 16px;
        border: 1px solid rgba(48, 49, 145, .12);
        border-radius: 14px;
        background: #f8f9ff;
    }

    .pay-total-label {
        color: var(--tw-text);
        font-size: 12.5px;
        font-weight: 900;
        text-transform: uppercase;
    }

    .pay-total-value {
        color: var(--tw-blue);
        font-size: clamp(21px, 2.3vw, 26px);
        font-weight: 900;
        line-height: 1.1;
        text-align: right;
    }

    .pay-help {
        display: grid;
        gap: 8px;
        padding: 14px 18px 16px;
        border-top: 1px solid var(--tw-line);
        background: #fbfcfe;
    }

    .pay-help-item {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--tw-muted);
        font-size: 11.5px;
        line-height: 1.35;
    }

    .pay-help-item svg {
        color: var(--tw-green);
        flex: 0 0 18px;
    }

    .pay-error {
        margin-bottom: 14px;
        padding: 12px 14px;
        border: 1px solid #fecaca;
        border-radius: 12px;
        background: #fef2f2;
        color: var(--tw-red);
        font-size: 13px;
        font-weight: 750;
    }

    @media (max-width: 900px) {
        body {
            margin-top: 0 !important;
        }

        section.navbarmain {
            padding-top: 104px !important;
        }

        main.navbarmain.upper-space {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }

        .pay-wrap {
            padding: 10px 12px 56px;
        }

        .pay-hero {
            grid-template-columns: 1fr;
            padding: 16px;
        }

        .pay-deadline {
            min-width: 0;
        }

        .pay-grid {
            grid-template-columns: 1fr;
        }

        .pay-main {
            order: 1;
        }

        .pay-rail {
            order: 2;
            position: static;
        }
    }

    @media (max-width: 640px) {
        .pay-title {
            font-size: 25px;
        }

        .pay-option-head {
            grid-template-columns: 40px minmax(0, 1fr);
        }

        .pay-badge {
            grid-column: 2;
            justify-self: start;
        }

        .pay-bank-grid {
            grid-template-columns: 1fr;
        }

        .pay-total {
            align-items: flex-start;
            flex-direction: column;
        }

        .pay-total-value {
            text-align: left;
        }
    }
</style>

<div class="pay-wrap" data-payment-page>
    <nav class="pay-crumb" aria-label="Breadcrumb">
        <a href="{{ route('home') }}">Home</a>
        <span>›</span>
        <a href="{{ route('air.flight-s') }}">Flight Results</a>
        <span>›</span>
        <span>Payment Options</span>
    </nav>

    @if($errors->has('error'))
        <div class="pay-error">{{ $errors->first('error') }}</div>
    @endif

    @if($errors->has('flex_error'))
        <div class="pay-error">{{ $errors->first('flex_error') }}</div>
    @endif

    <section class="pay-hero">
        <div>
            <div class="pay-kicker">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.5 8.8a1.5 1.5 0 0 1-1 0C7.5 20.5 4 18 4 13V6.5a1.5 1.5 0 0 1 1-1.4l6.5-2.4a1.5 1.5 0 0 1 1 0L19 5.1a1.5 1.5 0 0 1 1 1.4V13Z"/><path d="m9 12 2 2 4-4"/></svg>
                Booking on hold
            </div>
            <h1 class="pay-title">Choose how to complete your payment</h1>
            <p class="pay-subtitle">
                Your booking is reserved. Select a payment method below and complete payment before the hold expires so your ticket can be issued.
            </p>
        </div>

        @if($tktFormatted)
            <div class="pay-deadline">
                <div class="pay-deadline-label">Pay before</div>
                <div class="pay-deadline-time">{{ $tktFormatted }}@if($tktHours > 0) · {{ $tktHours }} hrs left @endif</div>
            </div>
        @endif
    </section>

    <div class="pay-grid">
        <main class="pay-main">
            <section class="pay-options" aria-label="Payment methods">
                <article class="pay-option is-active" data-pay-option="gateway">
                    <button class="pay-option-head" type="button" data-pay-trigger="gateway">
                        <span class="pay-icon">
                            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M7 15h3"/></svg>
                        </span>
                        <span>
                            <span class="pay-option-title">Pay online</span>
                            <span class="pay-option-sub">Use card, bank transfer, or USSD through the secure payment gateway.</span>
                        </span>
                        <span class="pay-badge">Instant ticketing</span>
                    </button>
                    <div class="pay-option-body">
                        <div class="pay-note">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                            <span>Payment is confirmed immediately. Once successful, TravelWheel will proceed with ticket issuance.</span>
                        </div>
                        <form method="POST" action="{{ route('flights.payment.gateway-ticket') }}" id="gw-ticket-form">
                            @csrf
                            <button type="submit" class="pay-action" id="gw-ticket-btn">
                                Pay {{ $fmt($total) }} now
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                            </button>
                        </form>
                    </div>
                </article>

                @if($bankAccounts !== [])
                <article class="pay-option" data-pay-option="bank">
                    <button class="pay-option-head" type="button" data-pay-trigger="bank">
                        <span class="pay-icon">
                            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10h18"/><path d="M5 10V8l7-4 7 4v2"/><path d="M6 10v8"/><path d="M10 10v8"/><path d="M14 10v8"/><path d="M18 10v8"/><path d="M4 18h16"/></svg>
                        </span>
                        <span>
                            <span class="pay-option-title">Bank transfer</span>
                            <span class="pay-option-sub">Transfer manually and submit your payment reference for verification.</span>
                        </span>
                        <span class="pay-badge manual">Manual review</span>
                    </button>
                    <div class="pay-option-body">
                        <div class="pay-note warn">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                            <span>Transfer the exact amount of <strong>{{ $fmt($total) }}</strong>. Ticketing starts after payment is verified.</span>
                        </div>
                        <div class="pay-bank-grid">
                            @foreach($bankAccounts as $account)
                                <div class="pay-bank">
                                    <div class="pay-bank-name">{{ $account['bank'] }}</div>
                                    <div class="pay-bank-number">{{ $account['account_number'] }}</div>
                                    <div class="pay-bank-holder">{{ $account['account_name'] }}</div>
                                    <button class="pay-copy" type="button" data-copy="{{ $account['account_number'] }}">Copy account</button>
                                </div>
                            @endforeach
                        </div>
                        <form method="POST" action="{{ route('flights.payment.bank-transfer') }}">
                            @csrf
                            <label class="pay-field">
                                <span class="pay-label">Payment reference</span>
                                <input class="pay-input" type="text" name="payment_reference" required minlength="3" maxlength="100" placeholder="Transaction ref, depositor name, or bank note">
                            </label>
                            <button type="submit" class="pay-action secondary">I have made payment</button>
                        </form>
                    </div>
                </article>
                @endif

                <article class="pay-option" data-pay-option="flex">
                    <button class="pay-option-head" type="button" data-pay-trigger="flex">
                        <span class="pay-icon">
                            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/><path d="M8 15h4"/><path d="M8 18h7"/></svg>
                        </span>
                        <span>
                            <span class="pay-option-title">TravelFlex instalments</span>
                            <span class="pay-option-sub">Apply to pay a down payment now and spread the remaining balance.</span>
                        </span>
                        <span class="pay-badge {{ $flexEligible ? '' : 'disabled' }}">{{ $flexEligible ? 'Available' : 'Eligibility required' }}</span>
                    </button>
                    <div class="pay-option-body">
                        @if($flexEligible)
                            <div class="pay-note">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                                <span>This fare qualifies for TravelFlex. You will continue to the instalment application before payment.</span>
                            </div>
                            <a class="pay-action" href="{{ route('flights.travelflex') }}">Apply for TravelFlex</a>
                        @else
                            <div class="pay-note danger">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                                <span>TravelFlex is only available for refundable fares with at least 14 days before departure.</span>
                            </div>
                            <button class="pay-action disabled" type="button" disabled>TravelFlex unavailable</button>
                        @endif
                    </div>
                </article>
            </section>
        </main>

        <aside class="pay-rail">
            <section class="pay-summary" aria-label="Booking summary">
                <div class="pay-summary-head">
                    <div class="pay-summary-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2h12a2 2 0 0 1 2 2v18l-4-2-4 2-4-2-4 2V4a2 2 0 0 1 2-2Z"/><path d="M8 7h8"/><path d="M8 11h8"/><path d="M8 15h5"/></svg>
                        Booking Summary
                    </div>
                    <div class="pay-summary-sub">Review your held itinerary before payment.</div>
                </div>
                <div class="pay-summary-body">
                    <div class="pay-route">
                        <span class="pay-route-icon">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21 16v-2l-8-5V3.5a1.5 1.5 0 0 0-3 0V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5L21 16Z"/></svg>
                        </span>
                        <span>
                            <span class="pay-route-title">{{ $routeLabel ?: 'Selected flight' }}</span>
                            <span class="pay-route-meta">{{ $tripLabel }} · {{ $cabinLabel }}</span>
                        </span>
                    </div>

                    @if(! empty($bookingRef))
                        <div class="pay-row"><span>Booking ref</span><strong>{{ $bookingRef }}</strong></div>
                    @endif
                    <div class="pay-row"><span>Fare type</span><strong>{{ $fareType }}</strong></div>
                    <div class="pay-row"><span>Airline</span><strong>{{ $flight['airline'] ?? 'Selected airline' }}</strong></div>

                    @if(! empty($breakdown))
                        <div style="height:1px;background:var(--tw-line-2);margin:9px 0;"></div>
                        @foreach($breakdown as $fb)
                            @php
                                $typeLabel = match ($fb['passengerType'] ?? 'ADT') {
                                    'ADT' => 'Adult',
                                    'CHD' => 'Child',
                                    'INF' => 'Infant',
                                    default => 'Passenger',
                                };
                                $qty = (int) ($fb['qty'] ?? 1);
                            @endphp
                            <div class="pay-row">
                                <span>{{ $typeLabel }} × {{ $qty }}</span>
                                <strong>{{ $fmt(((float) ($fb['totalFare'] ?? 0)) * $qty) }}</strong>
                            </div>
                        @endforeach
                    @endif

                    @if($extrasTotal > 0)
                        <div class="pay-row"><span>Extras</span><strong>{{ $fmt($extrasTotal) }}</strong></div>
                    @endif
                </div>

                <div class="pay-total">
                    <span class="pay-total-label">Trip total</span>
                    <span class="pay-total-value">{{ $fmt($total) }}</span>
                </div>

                <div class="pay-help">
                    <div class="pay-help-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.5 8.8a1.5 1.5 0 0 1-1 0C7.5 20.5 4 18 4 13V6.5a1.5 1.5 0 0 1 1-1.4l6.5-2.4a1.5 1.5 0 0 1 1 0L19 5.1a1.5 1.5 0 0 1 1 1.4V13Z"/><path d="m9 12 2 2 4-4"/></svg>
                        <span>Secure checkout with your booking held until the deadline.</span>
                    </div>
                    <div class="pay-help-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8Z"/><path d="M8 9h8"/><path d="M8 13h5"/></svg>
                        <span>Need help? Contact TravelWheel support before paying.</span>
                    </div>
                </div>
            </section>
        </aside>
    </div>
</div>

<script>
    document.querySelectorAll('[data-pay-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            document.querySelectorAll('[data-pay-option]').forEach((option) => {
                option.classList.toggle('is-active', option.dataset.payOption === trigger.dataset.payTrigger);
            });
        });
    });

    document.querySelectorAll('[data-copy]').forEach((button) => {
        button.addEventListener('click', async (event) => {
            event.stopPropagation();
            try {
                await navigator.clipboard.writeText(button.dataset.copy);
                const original = button.textContent;
                button.textContent = 'Copied';
                setTimeout(() => { button.textContent = original; }, 1400);
            } catch (_) {
                button.textContent = 'Copy failed';
                setTimeout(() => { button.textContent = 'Copy account'; }, 1400);
            }
        });
    });

    const gatewayForm = document.getElementById('gw-ticket-form');
    if (gatewayForm) {
        gatewayForm.addEventListener('submit', () => {
            const button = document.getElementById('gw-ticket-btn');
            if (button) {
                button.disabled = true;
                button.textContent = 'Processing payment...';
            }
        });
    }
</script>
@endcomponent
