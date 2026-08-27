{{-- resources/views/livewire/pages/flight/flight-travelflex.blade.php --}}
@component('layouts.app', ['title' => 'TravelFlex - Pay in Instalments'])

@php
    $bookingFlight  = session('bookingFlight', []);
    $mappedFlight   = $bookingFlight['flight'] ?? $bookingFlight;
    $segments       = $mappedFlight['segments'] ?? [];
    $firstSeg       = $segments[0] ?? [];
    $lastSeg        = !empty($segments) ? $segments[count($segments)-1] : [];
    $currency       = $mappedFlight['currency'] ?? 'NGN';
    $sym = match($currency) { 'NGN' => html_entity_decode('&#8358;', ENT_QUOTES, 'UTF-8'), 'USD' => '$', 'GBP' => html_entity_decode('&pound;', ENT_QUOTES, 'UTF-8'), 'EUR' => html_entity_decode('&euro;', ENT_QUOTES, 'UTF-8'), default => $currency . ' ' };
    $selectedExtras = session('selectedExtras', []);
    $extrasTotal    = 0.0;
    foreach ($selectedExtras as $category) {
        if (!is_array($category)) continue;
        foreach ($category as $item) {
            if (!is_array($item)) continue;
            $extrasTotal += isset($item['line_total'])
                ? (float) $item['line_total']
                : ((float) ($item['unit_price'] ?? 0)) * max(1, (int) ($item['quantity'] ?? 1));
        }
    }
    $totalPrice     = (float) ($mappedFlight['price'] ?? 0) + $extrasTotal;
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

    $bankAccounts = config('travelwheel.travelflex_bank_accounts', []);
    $travelFlexInterestRate = (float) config('travelwheel.travelflex_interest_rate', 0.04);
    $travelFlexInterestPercent = rtrim(rtrim(number_format($travelFlexInterestRate * 100, 2), '0'), '.');
    $travelFlexAdministrationFeeRate = (float) config('travelwheel.travelflex_administration_fee_rate', 0.01);
    $travelFlexAdministrationFeePercent = rtrim(rtrim(number_format($travelFlexAdministrationFeeRate * 100, 2), '0'), '.');
    $travelFlexInsuranceFeeRate = (float) config('travelwheel.travelflex_insurance_fee_rate', 0.015);
    $travelFlexInsuranceFeePercent = rtrim(rtrim(number_format($travelFlexInsuranceFeeRate * 100, 2), '0'), '.');
    $riskAssessment = app(\App\Services\TravelFlexRiskAssessmentService::class)->assess($mappedFlight, $selectedExtras);
    $minimumDownPercent = (int) ($riskAssessment['minimum_down_percent'] ?? 30);
    $maximumDownPercent = (int) ($riskAssessment['maximum_down_percent'] ?? 90);
    $downPercentStep = (int) ($riskAssessment['percentage_step'] ?? 10);
    $downPercentOptions = [];
    for ($percent = $minimumDownPercent; $percent <= $maximumDownPercent; $percent += $downPercentStep) {
        $downPercentOptions[] = $percent;
    }

    // Add these lines to define $isReturn, $isMulti, $multiLegs, $tripLabel
    $mf = $mappedFlight;
    $isReturn   = isset($mf['returnSegments']) && is_array($mf['returnSegments']) && count($mf['returnSegments']) > 0;
    $isMulti    = isset($mf['multiLegs']) && is_array($mf['multiLegs']) && count($mf['multiLegs']) > 0;
    $multiLegs  = $mf['multiLegs'] ?? [];
    $tripLabel  = $isReturn ? 'Round Trip' : ($isMulti ? 'Multi-City' : 'One Way');
    $returnDateLabel = $mf['returnDateLabel'] ?? '';
    $tfRetDate  = $mf['returnDateLabel'] ?? '';  // â† ADD THIS LINE
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

    /* â”€â”€ Layout â”€â”€ */
    .tf-outer { max-width: 1060px; margin: 0 auto; padding: 28px 16px 80px; }
    .tf-grid  { display: grid; grid-template-columns: 1fr 300px; gap: 22px; align-items: start; }
    .tf-main  { display: flex; flex-direction: column; gap: 0; }

    /* â”€â”€ Header gradient card â”€â”€ */
    .tf-hero { background: linear-gradient(135deg, var(--tf-navy) 0%, #312e81 50%, var(--tf-indigo) 100%); border-radius: 16px; padding: 26px 28px; margin-bottom: 22px; color: #fff; position: relative; overflow: hidden; }
    .tf-hero::before { content: ''; position: absolute; top: -40px; right: -40px; width: 200px; height: 200px; background: radial-gradient(circle, rgba(124,58,237,.35) 0%, transparent 70%); pointer-events: none; }
    .tf-hero-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(255,255,255,.15); color: white; border-radius: 999px; font-size: 11px; font-weight: 700; letter-spacing: .05em; margin-bottom: 10px; }
    .tf-hero-title { font-size: 22px; font-weight: 800; margin-bottom: 6px; color: white; }
    .tf-hero-sub   { font-size: 13px; opacity: .85; line-height: 1.65; max-width: 480px; color: white; }

    /* â”€â”€ Progress â”€â”€ */
    .tf-progress-wrap { background: rgba(255,255,255,.12); border-radius: 999px; height: 6px; margin-top: 18px; overflow: hidden; }
    .tf-progress-bar  { height: 100%; border-radius: 999px; background: linear-gradient(90deg, #a5b4fc, #fff); transition: width .45s cubic-bezier(.2,.9,.3,1); }

    /* â”€â”€ Step label â”€â”€ */
    .tf-step-label { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: var(--gray-400); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
    .tf-step-label span { width: 22px; height: 22px; border-radius: 50%; background: var(--tf-blue); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 800; }

    /* â”€â”€ Main card â”€â”€ */
    .tf-card { background: #fff; border: 1px solid var(--gray-200); border-radius: 14px; padding: 24px 24px; box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 0; }
    .tf-card + .tf-card { margin-top: 0; border-top: none; border-radius: 0 0 14px 14px; }
    .tf-step { display: none; }
    .tf-step.active { display: block; animation: tfFadeIn .3s ease both; }
    @keyframes tfFadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }

    /* â”€â”€ Ineligible notice â”€â”€ */
    .tf-ineligible { background: var(--tf-amber-lt); border: 1px solid #fed7aa; border-radius: 12px; padding: 20px 22px; display: flex; align-items: flex-start; gap: 16px; }
    .tf-ineligible-icon { font-size: 32px; flex-shrink: 0; }

    /* â”€â”€ Disclaimer â”€â”€ */
    .tf-disclaimer-box { background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 10px; padding: 18px 20px; max-height: 360px; overflow-y: auto; font-size: 12.5px; line-height: 1.75; color: var(--gray-700); margin-bottom: 20px; }
    .tf-disclaimer-box h4 { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: var(--gray-400); margin: 14px 0 6px; }
    .tf-disclaimer-box h4:first-child { margin-top: 0; }
    .tf-disclaimer-box p { margin-bottom: 8px; }
    .tf-agree-row { display: flex; align-items: flex-start; gap: 12px; padding: 14px; background: var(--tf-blue-lt); border: 1.5px solid var(--tf-blue-md); border-radius: 10px; cursor: pointer; }
    .tf-agree-row input[type=checkbox] { width: 18px; height: 18px; accent-color: var(--tf-blue); flex-shrink: 0; margin-top: 1px; cursor: pointer; }
    .tf-agree-row label { font-size: 13px; font-weight: 600; color: var(--tf-blue); line-height: 1.5; cursor: pointer; }

    /* â”€â”€ Form fields â”€â”€ */
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

    /* â”€â”€ Repayment plan cards â”€â”€ */
    .tf-schedule { display: flex; flex-direction: column; gap: 10px; margin: 16px 0; }
    .tf-installment { background: var(--tf-blue-lt); border: 1.5px solid var(--tf-blue-md); border-radius: 11px; padding: 14px 16px; }
    .tf-installment-head { font-size: 13px; font-weight: 800; color: var(--tf-navy); margin-bottom: 6px; display: flex; justify-content: space-between; align-items: center; }
    .tf-installment-due { font-size: 11px; color: var(--tf-blue); font-weight: 600; }
    .tf-installment-body { display: flex; gap: 16px; font-size: 12.5px; flex-wrap: wrap; }
    .tf-installment-body span { color: var(--gray-500); }
    .tf-installment-body strong { color: var(--gray-900); }
    .tf-installment-total { font-size: 13px; font-weight: 800; color: var(--tf-indigo); margin-left: auto; }

    /* â”€â”€ Summary strips â”€â”€ */
    .tf-summary-strip { background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 10px; padding: 14px 16px; margin-bottom: 14px; }
    .tf-sum-row { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid var(--gray-100); font-size: 13px; }
    .tf-sum-row:last-child { border-bottom: none; }
    .tf-sum-lbl { color: var(--gray-500); }
    .tf-sum-val { font-weight: 700; font-family: var(--mono); font-size: 12.5px; }
    .tf-total-row { display: flex; justify-content: space-between; align-items: center; padding: 14px 16px; background: linear-gradient(135deg, var(--tf-navy) 0%, var(--tf-indigo) 100%); border-radius: 10px; margin-bottom: 14px; }
    .tf-total-lbl { font-size: 14px; font-weight: 800; color: #fff; }
    .tf-total-val { font-size: 22px; font-weight: 800; color: #fff; font-family: var(--mono); }

    /* â”€â”€ Down payment highlight â”€â”€ */
    .tf-downpay-box { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; background: var(--tf-green-lt); border: 1.5px solid #a7f3d0; border-radius: 12px; margin-bottom: 14px; }
    .tf-downpay-label { font-size: 13px; font-weight: 700; color: var(--tf-green); }
    .tf-downpay-value { font-size: 22px; font-weight: 800; color: var(--tf-green); font-family: var(--mono); }
    .tf-downpay-sub { font-size: 11px; color: var(--tf-green); opacity: .8; }

    /* â”€â”€ Payment option cards â”€â”€ */
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

    /* â”€â”€ Buttons â”€â”€ */
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

    /* â”€â”€ Notice â”€â”€ */
    .tf-notice { display: flex; align-items: flex-start; gap: 9px; padding: 11px 14px; border-radius: 9px; font-size: 12.5px; margin-bottom: 16px; }
    .tf-notice.info  { background: var(--tf-blue-lt); color: var(--tf-blue); border: 1px solid var(--tf-blue-md); }
    .tf-notice.warn  { background: var(--tf-amber-lt); color: var(--tf-amber); border: 1px solid #fed7aa; }
    .tf-notice.green { background: var(--tf-green-lt); color: var(--tf-green); border: 1px solid #a7f3d0; }

    /* â”€â”€ Right rail â”€â”€ */
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

<style>
    :root {
        --tf-brand: #39328f;
        --tf-brand-700: #2f287c;
        --tf-green: #049a63;
        --tf-green-soft: #eefaf4;
        --tf-blue-soft: #f5f7ff;
        --tf-amber: #b7791f;
        --tf-amber-soft: #fff8ed;
        --tf-red: #c62828;
        --tf-red-soft: #fff3f3;
        --tf-ink: #101828;
        --tf-muted: #667085;
        --tf-subtle: #98a2b3;
        --tf-line: #e6e9f0;
        --tf-soft: #f7f8fb;
        --tf-card: #ffffff;
        --gray-50: #f7f8fb;
        --gray-100: #eef1f6;
        --gray-200: #e6e9f0;
        --gray-300: #cfd4df;
        --gray-400: #98a2b3;
        --gray-500: #667085;
        --gray-700: #344054;
        --gray-900: #101828;
        --tf-blue: var(--tf-brand);
        --tf-blue-lt: var(--tf-blue-soft);
        --tf-blue-md: rgba(57,50,143,.16);
        --tf-indigo: var(--tf-brand);
        --tf-navy: var(--tf-ink);
        --tf-green-lt: var(--tf-green-soft);
        --tf-amber-lt: var(--tf-amber-soft);
        --tf-red-lt: var(--tf-red-soft);
    }

    body { background: #f7f8fb; color: var(--tf-ink); font-size: 14px; }
    .tf-outer { max-width: 1180px; padding: 24px 18px 80px; }
    .tf-grid { grid-template-columns: minmax(0, 1fr) 340px; gap: 18px; }
    .tf-hero {
        background: #fff;
        border: 1px solid var(--tf-line);
        border-radius: 8px;
        padding: 22px;
        color: var(--tf-ink);
        margin-bottom: 16px;
        box-shadow: 0 14px 36px rgba(16,24,40,.06);
    }
    .tf-hero::before { display: none; }
    .tf-hero-badge { background: var(--tf-blue-soft); color: var(--tf-brand); border: 1px solid rgba(57,50,143,.16); border-radius: 999px; padding: 6px 10px; font-size: 11px; letter-spacing: .02em; }
    .tf-hero-badge svg { stroke: var(--tf-brand); }
    .tf-hero-title { color: var(--tf-ink); font-size: clamp(22px, 2.5vw, 34px); line-height: 1.12; letter-spacing: 0; max-width: 680px; }
    .tf-hero-sub { color: var(--tf-muted); opacity: 1; font-size: 14px; max-width: 720px; }
    .tf-progress-wrap { background: #eef1f6; height: 7px; margin-top: 20px; }
    .tf-progress-bar { background: linear-gradient(90deg, var(--tf-brand), var(--tf-green)); }
    .tf-flow-pills { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 16px; }
    .tf-flow-pill { display: inline-flex; align-items: center; gap: 7px; min-height: 30px; padding: 6px 10px; border-radius: 999px; background: var(--tf-soft); color: var(--tf-muted); font-size: 12px; font-weight: 700; }
    .tf-flow-pill strong { color: var(--tf-brand); font-weight: 800; }

    .tf-card, .tf-rail-card, .tf-eligibility-card {
        background: #fff;
        border: 1px solid var(--tf-line);
        border-radius: 8px;
        box-shadow: 0 12px 32px rgba(16,24,40,.055);
    }
    .tf-card { padding: 22px; }
    .tf-step.active, .tf-pay-option.active .tf-pay-option-body { animation: tfFadeIn .28s ease both; }
    @keyframes tfFadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    .tf-step-label { color: var(--tf-muted); font-size: 12px; letter-spacing: .05em; }
    .tf-step-label span { width: 28px; height: 28px; background: var(--tf-brand); box-shadow: 0 8px 18px rgba(57,50,143,.16); }

    .tf-disclaimer-box, .tf-summary-strip, .tf-bank-card { background: #fbfcfe; border: 1px solid var(--tf-line); border-radius: 8px; }
    .tf-disclaimer-box { color: var(--tf-muted); font-size: 13px; line-height: 1.72; max-height: 340px; }
    .tf-disclaimer-box h4 { color: var(--tf-ink); font-size: 11px; letter-spacing: .05em; }
    .tf-agree-row { background: var(--tf-blue-soft); border: 1px solid rgba(57,50,143,.16); border-radius: 8px; transition: border-color .18s, box-shadow .18s, background .18s; }
    .tf-agree-row:hover { border-color: rgba(57,50,143,.28); box-shadow: 0 10px 22px rgba(16,24,40,.05); }
    .tf-agree-row input[type=checkbox] { accent-color: var(--tf-brand); }
    .tf-agree-row label { color: var(--tf-ink); font-size: 13px; font-weight: 650; }

    .tf-label, .tf-ref-label { color: var(--tf-muted); font-size: 11px; letter-spacing: .045em; }
    .tf-input, .tf-select, .tf-ref-input {
        height: 48px;
        border: 1px solid var(--tf-line);
        border-radius: 8px;
        background: #fff;
        color: var(--tf-ink);
        transition: border-color .18s, box-shadow .18s, background .18s;
    }
    .tf-input:focus, .tf-select:focus, .tf-ref-input:focus { border-color: var(--tf-brand); box-shadow: 0 0 0 4px rgba(57,50,143,.08); }
    .tf-input[readonly], .tf-input[disabled], .tf-select:disabled { background: var(--tf-soft); color: var(--tf-muted); }
    .tf-locked-badge { color: var(--tf-subtle); font-size: 11px; }

    .tf-installment { background: #fff; border: 1px solid var(--tf-line); border-radius: 8px; transition: transform .18s, box-shadow .18s; }
    .tf-installment:hover { transform: translateY(-1px); box-shadow: 0 10px 24px rgba(16,24,40,.06); }
    .tf-installment-head { color: var(--tf-ink); gap: 12px; }
    .tf-installment-total { color: var(--tf-brand); white-space: nowrap; }
    .tf-sum-row { border-bottom: 1px solid #eef1f6; gap: 14px; }
    .tf-sum-lbl { color: var(--tf-muted); }
    .tf-sum-val { color: var(--tf-ink); overflow-wrap: anywhere; text-align: right; }
    .tf-total-row { background: var(--tf-ink); border-radius: 8px; }
    .tf-downpay-box { background: var(--tf-green-soft); border: 1px solid #bee9d3; border-radius: 8px; }
    .tf-downpay-label, .tf-downpay-value { color: var(--tf-green); }

    .tf-pay-option { border: 1px solid var(--tf-line); border-radius: 8px; transition: border-color .18s, box-shadow .18s, transform .18s; }
    .tf-pay-option { display: none !important; }
    .tf-pay-option:hover { transform: translateY(-1px); box-shadow: 0 12px 26px rgba(16,24,40,.06); }
    .tf-pay-option.active { border-color: rgba(57,50,143,.42); box-shadow: 0 12px 28px rgba(57,50,143,.08); }
    .tf-pay-radio { border-color: #cfd4df; }
    .tf-pay-option.active .tf-pay-radio { border-color: var(--tf-brand); background: var(--tf-brand); }
    .tf-pay-option-icon { border-radius: 8px; color: var(--tf-brand); background: var(--tf-blue-soft) !important; font-size: 0; }
    .tf-pay-option-body { border-top: 1px solid #eef1f6; }
    .tf-bank-card { padding: 12px 92px 12px 14px; min-height: 76px; }
    .tf-bank-acct { color: var(--tf-ink); overflow-wrap: anywhere; }
    .tf-copy-btn { height: 32px; border: 1px solid var(--tf-line); border-radius: 8px; color: var(--tf-muted); }
    .tf-copy-btn:hover { border-color: var(--tf-brand); color: var(--tf-brand); background: var(--tf-blue-soft); }

    .tf-btn-primary, .tf-btn-secondary, .tf-btn-ghost, .tf-btn-pay, .tf-btn-bank {
        border-radius: 8px;
        min-height: 48px;
        transition: transform .18s, box-shadow .18s, background .18s, border-color .18s;
    }
    .tf-btn-primary { background: var(--tf-brand); box-shadow: 0 10px 22px rgba(57,50,143,.18); }
    .tf-btn-primary:hover { background: var(--tf-brand-700); }
    .tf-btn-secondary, .tf-btn-pay { background: var(--tf-green); box-shadow: 0 10px 22px rgba(4,154,99,.16); }
    .tf-btn-ghost { border: 1px solid var(--tf-line); color: var(--tf-muted); }
    .tf-btn-ghost:hover { border-color: #cfd4df; color: var(--tf-ink); background: var(--tf-soft); }
    .tf-btn-bank { background: var(--tf-ink); }
    .tf-btn-primary:hover, .tf-btn-secondary:hover, .tf-btn-pay:hover, .tf-btn-bank:hover { transform: translateY(-1px); }

    .tf-notice { border-radius: 8px; line-height: 1.55; }
    .tf-notice.info { background: var(--tf-blue-soft); color: var(--tf-brand); border: 1px solid rgba(57,50,143,.16); }
    .tf-notice.warn { background: var(--tf-amber-soft); color: var(--tf-amber); border: 1px solid #f2d8ac; }
    .tf-notice.green { background: var(--tf-green-soft); color: var(--tf-green); border: 1px solid #bee9d3; }
    .tf-ineligible { background: var(--tf-amber-soft); border: 1px solid #f2d8ac; border-radius: 8px; }
    .tf-ineligible-icon { width: 38px; height: 38px; border-radius: 999px; background: #fff; display: inline-flex; align-items: center; justify-content: center; color: var(--tf-amber); font-size: 0; flex: 0 0 auto; }

    .tf-rail-card { border-radius: 8px; }
    .tf-rail-head { background: var(--tf-ink); padding: 16px 18px; }
    .tf-rail-title { display: flex; align-items: center; gap: 8px; }
    .tf-rail-row { border-bottom: 1px solid #eef1f6; padding: 8px 0; gap: 12px; }
    .tf-rail-lbl { color: var(--tf-muted); }
    .tf-rail-val { overflow-wrap: anywhere; }
    .tf-rail-total { background: #fbfcfe; border-top: 1px solid var(--tf-line); }
    .tf-rail-total-val { color: var(--tf-green); }
    .tf-flex-logo { color: var(--tf-brand); border-top: 1px solid #eef1f6; }

    @media (max-width: 960px) {
        body { margin-top: 0; }
        .tf-grid { grid-template-columns: 1fr; }
        .tf-grid > aside { order: 2; }
        .tf-rail-card { position: static; }
    }
    @media (max-width: 640px) {
        .tf-outer { padding: 14px 12px 64px; }
        .tf-hero, .tf-card { padding: 16px; }
        .tf-field-grid { grid-template-columns: 1fr; }
        .tf-btn-row { flex-direction: column; }
        .tf-btn-row > * { width: 100%; }
        .tf-downpay-box, .tf-total-row, .tf-rail-total { align-items: flex-start; flex-direction: column; gap: 8px; }
        .tf-sum-row { flex-direction: column; gap: 4px; }
        .tf-sum-val, .tf-total-val, .tf-downpay-value { text-align: left; }
        .tf-pay-option-head { align-items: flex-start; }
    }
</style>

<div class="tf-outer">

    {{-- â”€â”€ Hero Header â”€â”€ --}}
    <div class="tf-hero">
        <div style="position:relative;z-index:2;">
            <div class="tf-hero-badge">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                TravelFlex Instalment Plan
            </div>
            <div class="tf-hero-title">Pay for your flight in easy instalments</div>
            <div class="tf-hero-sub">
                Secure your seat today with a minimum {{ $minimumDownPercent }}% down payment for this fare.
                Pay the balance over your chosen repayment period at a fixed {{ $travelFlexInterestPercent }}% interest rate.
                Provided by a licensed third-party lender.
            </div>
            <div class="tf-flow-pills">
                <div class="tf-flow-pill"><strong>1</strong> Review terms</div>
                <div class="tf-flow-pill"><strong>2</strong> Build plan</div>
                <div class="tf-flow-pill"><strong>3</strong> Apply</div>
                <div class="tf-flow-pill"><strong>4</strong> Pay deposit</div>
            </div>
            <div class="tf-progress-wrap" id="tfProgressWrap">
                <div class="tf-progress-bar" id="tfProgress" style="width: {{ $eligible ? '20%' : '0%' }};"></div>
            </div>
        </div>
    </div>

    @if($errors->has('error'))
    <div class="tf-notice" style="background:#fef2f2;color:#b91c1c;border:1px solid #fca5a5;margin-bottom:22px;">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>{{ $errors->first('error') }}</span>
    </div>
    @endif

    @if(!$eligible)
    {{-- â”€â”€ Ineligibility Notice â”€â”€ --}}
    <div class="tf-ineligible" style="margin-bottom: 22px;">
        <div class="tf-ineligible-icon">
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
        </div>
        <div>
            <div style="font-size:16px;font-weight:800;color:#92400e;margin-bottom:6px;">TravelFlex Not Available for This Booking</div>
            <div style="font-size:13.5px;color:#78350f;line-height:1.65;">
                TravelFlex requires a minimum of <strong>14 days</strong> between today and the travel date.
                Your flight departs on <strong>{{ $departDateLabel }}</strong>
                @if($daysToDepart > 0) ({{ $daysToDepart }} day{{ $daysToDepart !== 1 ? 's' : '' }} away) @else - which has already passed or is today @endif.
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

        {{-- â”€â”€ Main Steps Column â”€â”€ --}}
        <div>

            {{-- â•â• STEP 0: DISCLAIMER â•â• --}}
            <div :class="{ 'tf-step active': true }" x-show="step === 0" x-transition>
                <div class="tf-card">
                    <div class="tf-step-label"><span>1</span> Legal Disclaimer &amp; Agreement</div>

                    <div class="tf-notice info">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span>Please read this agreement carefully before proceeding. You must agree to all terms to use TravelFlex.</span>
                    </div>

                    @include('livewire.pages.flight.partials.fastcredit-agreement', ['class' => 'tf-disclaimer-box'])

                    <div class="tf-agree-row" @click="toggleAgree()">
                        <input type="checkbox" id="tfAgree" :checked="agreed" @click.stop="toggleAgree()">
                        <label for="tfAgree" @click.prevent>
                            I confirm that I have read, understood and agreed to the above terms and conditions.
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

            {{-- â•â• STEP 1: CALCULATOR â•â• --}}
            <div x-show="step === 1" x-transition>
                <div class="tf-card">
                    <div class="tf-step-label"><span>2</span> Payment Calculator</div>

                    <div class="tf-field-grid">
                        {{-- Travel Date - prefilled and locked --}}
                        <div class="tf-field">
                            <div class="tf-label">Travel Date</div>
                            <input class="tf-input" type="text" value="{{ $departDateLabel }}" readonly>
                            <div class="tf-locked-badge">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                Auto-filled from booking
                            </div>
                        </div>

                        {{-- Ticket Cost - prefilled and locked --}}
                        <div class="tf-field">
                            <div class="tf-label">Ticket Cost</div>
                            <input class="tf-input" type="text" :value="formatCurrency(ticketCost)" readonly>
                            <div class="tf-locked-badge">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                Fixed from your booking
                            </div>
                        </div>

                        {{-- Down Payment % - risk-based minimum, customer may increase it --}}
                        <div class="tf-field">
                            <div class="tf-label">Down Payment % <span class="tf-req">*</span></div>
                            <select class="tf-select" x-model="downPercent" @change="onDownPercentChange()">
                                @foreach($downPercentOptions as $percent)
                                    <option value="{{ $percent }}">{{ $percent }}%{{ $percent === $minimumDownPercent ? ' (Minimum for this fare)' : '' }}</option>
                                @endforeach
                            </select>
                            <div class="tf-locked-badge">
                                Includes airline refund penalties, selected extras, and a cancellation-risk buffer.
                            </div>
                        </div>

                        {{-- Down Payment Amount - computed --}}
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
                                        <span x-text="inst.label + ' - ' + inst.dueDate"></span>
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
                            <div class="tf-sum-row"><span class="tf-sum-lbl">Estimated Cancellation Cost</span><span class="tf-sum-val" x-text="formatCurrency(estimatedCancellationCost)"></span></div>
                            <div class="tf-sum-row"><span class="tf-sum-lbl">Remaining Balance</span><span class="tf-sum-val" x-text="formatCurrency(remainingBalance)"></span></div>
                            <div class="tf-sum-row"><span class="tf-sum-lbl">Administration Fee ({{ $travelFlexAdministrationFeePercent }}%)</span><span class="tf-sum-val" x-text="formatCurrency(administrationFee)"></span></div>
                            <div class="tf-sum-row"><span class="tf-sum-lbl">Insurance Fee ({{ $travelFlexInsuranceFeePercent }}%)</span><span class="tf-sum-val" x-text="formatCurrency(insuranceFee)"></span></div>
                            <div class="tf-sum-row"><span class="tf-sum-lbl">Total Interest ({{ $travelFlexInterestPercent }}%/period)</span><span class="tf-sum-val" x-text="formatCurrency(totalInterest)"></span></div>
                        </div>
                        <div class="tf-total-row">
                            <span class="tf-total-lbl">Total Payable (Down + Fees + Instalments)</span>
                            <span class="tf-total-val" x-text="formatCurrency(grandTotal)"></span>
                        </div>
                    </div>

                    <div class="tf-btn-row">
                        <button type="button" class="tf-btn-ghost" @click="step = 0; setProgress(20)">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                            Back
                        </button>
                        <button type="button" class="tf-btn-primary" @click="calculate()" x-show="!calculated || repaymentPlan !== lastPlan">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                            Calculate Plan
                        </button>
                        <button type="button" class="tf-btn-secondary" x-show="calculated && repaymentPlan === lastPlan" @click="proceedToPayment()">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            Continue to Application
                        </button>
                    </div>
                </div>
            </div>

            {{-- â•â• STEP 2: PAY DOWN PAYMENT â•â• --}}
            <div x-show="step === 2" x-transition>
                <div class="tf-card">
                    <div class="tf-step-label"><span>3</span> Review Plan &amp; Apply</div>

                    <div class="tf-downpay-box" style="flex-direction:column;align-items:stretch;gap:0;">
                        <div class="tf-downpay-label" style="margin-bottom:10px;">Due After Approval</div>
                        <div style="display:flex;flex-direction:column;">
                            <div class="tf-sum-row" style="border-bottom:1px solid #bee9d3;">
                                <span class="tf-sum-lbl" style="color:var(--tf-green);">Down payment</span>
                                <span class="tf-sum-val" style="color:var(--tf-green);" x-text="formatCurrency(downPaymentAmount)"></span>
                            </div>
                            <div class="tf-sum-row" style="border-bottom:none;">
                                <span class="tf-sum-lbl" style="color:var(--tf-green);">Fast Credit administration &amp; insurance fees</span>
                                <span class="tf-sum-val" style="color:var(--tf-green);" x-text="formatCurrency(administrationFee + insuranceFee)"></span>
                            </div>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;padding-top:12px;border-top:1.5px solid #a7f3d0;">
                            <span class="tf-downpay-sub">Total due now</span>
                            <span class="tf-downpay-value" x-text="formatCurrency(upfrontPaymentTotal)"></span>
                        </div>
                    </div>

                    <div class="tf-notice warn">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span>This down payment is <strong>non-refundable</strong> once confirmed. Your flight will be booked and held immediately after payment.</span>
                    </div>

                    <div class="tf-notice warn">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span>Your remaining balance (<strong x-text="formatCurrency(remainingBalance)"></strong>) must be paid in full <strong>before your travel date</strong>. Your repayment schedule is structured to complete at least 14 days before departure.</span>
                    </div>

                    {{-- Plan recap --}}
                    <div class="tf-summary-strip" style="margin-bottom:20px;">
                        <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--gray-400);margin-bottom:8px;">Plan Summary</div>
                        <div class="tf-sum-row"><span class="tf-sum-lbl">Flight</span>
                            <span class="tf-sum-val" style="font-family:var(--font);">{{ ($firstSeg['from']??'') }} &rarr; {{ ($lastSeg['to']??'') }}</span>
                        </div>
                        <div class="tf-sum-row"><span class="tf-sum-lbl">Travel Date</span>
                            <span class="tf-sum-val" style="font-family:var(--font);">{{ $departDateLabel }}</span>
                        </div>
                        @if($isReturn)
                        <div class="tf-sum-row"><span class="tf-sum-lbl">Return Date</span>
                            <span class="tf-sum-val" style="font-family:var(--font);">{{ $returnDateLabel ?? '' }}</span>
                        </div>
                        @endif
                        @if($isMulti)
                        <div class="tf-sum-row"><span class="tf-sum-lbl">Total Legs</span>
                            <span class="tf-sum-val" style="font-family:var(--font);">{{ 1 + count($multiLegs) }} flights</span>
                        </div>
                        @endif
                        <div class="tf-sum-row"><span class="tf-sum-lbl">Trip Type</span>
                            <span class="tf-sum-val" style="font-family:var(--font);">{{ $tripLabel }}</span>
                        </div>
                        <div class="tf-sum-row"><span class="tf-sum-lbl">Repayment Plan</span>
                            <span class="tf-sum-val" style="font-family:var(--font);" x-text="repaymentPlan"></span>
                        </div>
                        <div class="tf-sum-row"><span class="tf-sum-lbl">Instalments</span>
                            <span class="tf-sum-val" style="font-family:var(--font);" x-text="schedule.length + ' payment(s)'"></span>
                        </div>
                        <div class="tf-sum-row"><span class="tf-sum-lbl">Total Payable</span>
                            <span class="tf-sum-val" x-text="formatCurrency(grandTotal)"></span>
                        </div>
                    </div>

                    {{-- â”€â”€ Payment Option 1: Bank Transfer â”€â”€ --}}
                    <form method="POST" action="{{ route('flights.travelflex.application') }}" id="tf-apply-form">
                        @csrf
                        <input type="hidden" name="down_percent" :value="downPercent" x-bind:value="downPercent">
                        <input type="hidden" name="repayment_plan" :value="repaymentPlan" x-bind:value="repaymentPlan">
                        <button type="submit" class="tf-btn-pay" id="tf-apply-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            Continue to Application
                        </button>
                    </form>

                    @if(false)
                    <div class="tf-pay-option" :class="{ active: payOption === 'bank' }" @click="payOption = 'bank'">
                        <div class="tf-pay-option-head">
                            <div class="tf-pay-radio"><div class="tf-pay-radio-dot"></div></div>
                            <div class="tf-pay-option-icon" style="background:#e0f2fe;">
                                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 10 9-6 9 6"/><path d="M5 10v9"/><path d="M19 10v9"/><path d="M3 19h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/></svg>
                            </div>
                            <div>
                                <div style="font-size:14px;font-weight:800;color:var(--gray-900);">Direct Bank Transfer</div>
                                <div style="font-size:12px;color:var(--gray-500);margin-top:2px;">Transfer down payment to our account</div>
                            </div>
                            <!-- <span style="margin-left:auto;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;background:var(--tf-amber-lt);color:var(--tf-amber);flex-shrink:0;">Manual</span> -->
                        </div>
                        <div class="tf-pay-option-body">
                            <div class="tf-notice warn" style="margin-top:12px">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                <span>Transfer exactly <strong x-text="formatCurrency(upfrontPaymentTotal)"></strong>. Your plan will be activated once payment is verified (2-4 hrs).</span>
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
                                <input type="hidden" name="pay_method" value="bank_transfer">
                                <input type="hidden" name="down_percent" :value="downPercent" x-bind:value="downPercent">
                                <input type="hidden" name="repayment_plan" :value="repaymentPlan" x-bind:value="repaymentPlan">
                                <button type="submit" class="tf-btn-bank">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    Continue to Application
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- â”€â”€ Payment Option 2: Gateway â”€â”€ --}}
                    <div class="tf-pay-option" :class="{ active: payOption === 'gateway' }" @click="payOption = 'gateway'">
                        <div class="tf-pay-option-head">
                            <div class="tf-pay-radio"><div class="tf-pay-radio-dot"></div></div>
                            <div class="tf-pay-option-icon" style="background:#f0fdf4;">
                                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M7 15h2"/><path d="M12 15h5"/></svg>
                            </div>
                            <div>
                                <div style="font-size:14px;font-weight:800;color:var(--gray-900);">Pay Online</div>
                                <div style="font-size:12px;color:var(--gray-500);margin-top:2px;">Card, bank transfer or USSD - instant confirmation</div>
                            </div>
                            <!-- <span style="margin-left:auto;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;background:var(--tf-green-lt);color:var(--tf-green);flex-shrink:0;">Instant</span> -->
                        </div>
                        <div class="tf-pay-option-body">
                            <div class="tf-notice green" style="margin-top:12px">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                                <span>Payment is processed securely. Your booking will be confirmed and plan activated <strong>immediately</strong>.</span>
                            </div>
                            <form method="POST" action="{{ route('flights.travelflex.application') }}" id="tf-gw-form" style="margin-top:12px;">
                                @csrf
                                <input type="hidden" name="pay_method" value="gateway">
                                <input type="hidden" name="down_percent" :value="downPercent" x-bind:value="downPercent">
                                <input type="hidden" name="repayment_plan" :value="repaymentPlan" x-bind:value="repaymentPlan">
                                <button type="submit" class="tf-btn-pay" id="tf-gw-btn">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                    Pay <span x-text="formatCurrency(upfrontPaymentTotal)" style="margin:0 4px;"></span> Now
                                </button>
                            </form>
                        </div>
                    </div>

                    @endif
                    <div class="tf-notice green" style="margin-top:12px">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                        <span>No payment is due now. Fast Credit reviews your application first. If approved, you will receive a secure link to choose online payment or bank transfer.</span>
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

        {{-- â”€â”€ Right Rail â”€â”€ --}}
        <aside>
            <div class="tf-rail-card">
                <div class="tf-rail-head">
                    <div class="tf-rail-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
                        TravelFlex
                    </div>
                    <div class="tf-rail-sub">Your instalment plan summary</div>
                </div>
                <div class="tf-rail-body">
                    @php
                        $tfOutFirst = $firstSeg ?? [];
                        $tfOutLast  = !empty($segments) ? $segments[count($segments)-1] : [];
                        $tfOutRoute = ($tfOutFirst['from']??'') . ' -> ' . ($tfOutLast['to']??'');

                        $tfRetFirst = ($mf['returnSegments'][0] ?? []);
                    $tfRetLast  = !empty($mf['returnSegments']) ? $mf['returnSegments'][count($mf['returnSegments'])-1] : [];
                    $tfRetRoute = ($tfRetFirst['from']??'') . ' -> ' . ($tfRetLast['to']??'');

                    $tfIsReturn = count($mf['returnSegments'] ?? []) > 0;
                    $tfIsMulti  = count($mf['multiLegs'] ?? []) > 0;
                    $tfTripLabel= $tfIsReturn ? 'Round Trip' : ($tfIsMulti ? 'Multi-City' : 'One Way');
                    $tfRouteLines = [];
                    if ($tfIsMulti) {
                        foreach (($mf['multiLegs'] ?? []) as $li => $leg) {
                            $tfRouteLines[] = [
                                'label' => 'Leg ' . ($li + 1),
                                'route' => ($leg['from'] ?? '') . ' -> ' . ($leg['to'] ?? ''),
                                'date'  => $leg['departDateLabel'] ?? '',
                            ];
                        }
                    }
                @endphp

                    <div class="tf-rail-row">
                        <span class="tf-rail-lbl">Flight</span>
                        <span class="tf-rail-val" style="font-size:13px;font-weight:800;">
                        @if($tfIsMulti)
                            @foreach($tfRouteLines as $line)
                                <div>{{ $line['route'] }}</div>
                                @if(!empty($line['date']))
                                    <span style="font-size:11px;color:var(--gray-400);">{{ $line['label'] }} · {{ $line['date'] }}</span>
                                @endif
                            @endforeach
                        @elseif($tfIsReturn)
                            {{ $tfOutRoute }}<br><span style="font-size:11px;color:var(--gray-400);">{{ $tfRetRoute }}</span>
                        @else
                            {{ $tfOutRoute }}
                            @endif
                        </span>
                    </div>
                    <div class="tf-rail-row">
                        <span class="tf-rail-lbl">Trip Type</span>
                        <span class="tf-rail-val">{{ $tfTripLabel }}</span>
                    </div>
                    <div class="tf-rail-row">
                        <span class="tf-rail-lbl">Travel Date</span>
                        <span class="tf-rail-val" style="font-size:12px">{{ $departDateLabel }}</span>
                    </div>
                    @if($tfIsReturn && $tfRetDate)
                    <div class="tf-rail-row">
                        <span class="tf-rail-lbl">Return Date</span>
                        <span class="tf-rail-val" style="font-size:11.5px;">{{ $tfRetDate }}</span>
                    </div>
                    @endif

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
                        <span class="tf-rail-val" x-text="repaymentPlan || '-'"></span>
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
                    Travel date at least 14 days away ({{ $daysToDepart }} days)
                </div>
                <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;margin-bottom:8px;color:var(--tf-green)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                    Minimum {{ $minimumDownPercent }}% down payment for this fare
                </div>
                <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--gray-400)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    {{ $travelFlexInterestPercent }}% interest per repayment interval
                </div>
            </div>
        </aside>

    </div>{{-- /tf-grid --}}
    @endif

</div>

<script>
function travelFlex() {
    return {
        // â”€â”€ State â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        step:              0,
        agreed:            false,
        ticketCost:        {{ $totalPrice }},
        travelDateISO:     '{{ $departDateISO }}',
        daysToDepart:      {{ $daysToDepart }},
        sym:               '{{ $sym }}',

        downPercent:       {{ $minimumDownPercent }},
        minimumDownPercent: {{ $minimumDownPercent }},
        estimatedCancellationCost: {{ (float) ($riskAssessment['estimated_cancellation_cost'] ?? 0) }},
        downPaymentAmount: 0,
        remainingBalance:  0,
        administrationFee: 0,
        insuranceFee: 0,
        upfrontPaymentTotal: 0,
        repaymentPlan:     '',
        lastPlan:          '',
        repaymentOptions:  [],
        schedule:          [],
        totalInterest:     0,
        grandTotal:        0,
        calculated:        false,
        payOption:         '',

        // â”€â”€ Init â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        init() {
            this.buildRepaymentOptions();
            this.onDownPercentChange();
        },

        // â”€â”€ Helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

        // â”€â”€ Build repayment options based on days to departure â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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
                { label: '6 months',  value: '6 months',  days: 180 },
                { label: '7 months',  value: '7 months',  days: 210 },
                { label: '8 months',  value: '8 months',  days: 240 },
                { label: '9 months',  value: '9 months',  days: 270 },
                { label: '10 months', value: '10 months', days: 300 },
                { label: '11 months', value: '11 months', days: 330 },
                { label: '12 months', value: '12 months', days: 360 },
            ];
            this.repaymentOptions = allOptions.filter(o => safedays >= o.days);
        },

        onDownPercentChange() {
            this.downPaymentAmount = this.ticketCost * (this.downPercent / 100);
            this.remainingBalance  = this.ticketCost - this.downPaymentAmount;
            this.administrationFee = Math.round((this.remainingBalance * {{ $travelFlexAdministrationFeeRate }}) * 100) / 100;
            this.insuranceFee = Math.round((this.remainingBalance * {{ $travelFlexInsuranceFeeRate }}) * 100) / 100;
            this.upfrontPaymentTotal = Math.round((this.downPaymentAmount + this.administrationFee + this.insuranceFee) * 100) / 100;
            this.calculated = false; // force recalculate
        },

        onPlanChange() {
            this.calculated = false;
        },

        // â”€â”€ Parse repayment label into interval days and instalment count â”€â”€â”€â”€â”€â”€
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

        // â”€â”€ Main calculation â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        calculate() {
            if (!this.repaymentPlan) return alert('Please select a repayment plan.');

            this.onDownPercentChange();

            const parsed      = this.parseRepaymentLabel(this.repaymentPlan);
            const intervalDays= parsed.unitDays;
            const numPeriods  = parsed.count;
            const RATE        = {{ $travelFlexInterestRate }};

            // Instalment schedule proportions (must mirror _normalizeTravelFlexPlan on the server)
            const fixedProportions = {
                1: [1.0],
                2: [0.5, 0.5],
                3: [0.4, 0.3, 0.3],
                4: [0.25, 0.25, 0.25, 0.25],
                5: [0.2, 0.2, 0.2, 0.2, 0.2],
            };
            const instalmentCount = Math.max(1, Math.min(12, numPeriods));
            const schedule = fixedProportions[instalmentCount]
                || Array(instalmentCount).fill(1 / instalmentCount);

            const ordinals = ['1st', '2nd', '3rd', '4th', '5th'];
            let   dueDate  = new Date();
            dueDate.setDate(dueDate.getDate() + intervalDays);

            let totalInterest = 0;
            let principalAllocated = 0;
            this.schedule = schedule.map((portion, i) => {
                if (i > 0) dueDate.setDate(dueDate.getDate() + intervalDays);
                const isLast   = i === schedule.length - 1;
                const interest = this.remainingBalance * RATE;
                let principal  = isLast
                    ? this.remainingBalance - principalAllocated
                    : portion * this.remainingBalance;
                principal = Math.round(principal * 100) / 100;
                principalAllocated += principal;
                const total    = principal + interest;
                totalInterest += interest;
                return {
                    label:     (ordinals[i] || (i+1)+'th') + ' Payment',
                    dueDate:   dueDate.toDateString(),
                    principal: principal,
                    interest:  Math.round(interest  * 100) / 100,
                    total:     Math.round(total      * 100) / 100,
                };
            });

            this.totalInterest = Math.round(totalInterest * 100) / 100;
            this.grandTotal    = Math.round((this.ticketCost + this.totalInterest + this.administrationFee + this.insuranceFee) * 100) / 100;
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

// Prevent double-submit on application handoff form
document.addEventListener('DOMContentLoaded', function () {
    const applyForm = document.getElementById('tf-apply-form');
    if (applyForm) {
        applyForm.addEventListener('submit', function () {
            const btn = document.getElementById('tf-apply-btn');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Opening application...';
            }
        });
    }
});
</script>
@endcomponent
