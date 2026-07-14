{{-- resources/views/livewire/pages/flight/flight-travelflex-pending.blade.php --}}
@component('layouts.app', ['title' => 'TravelFlex - Plan Pending Activation'])

@php
    $bookingFlight = session('bookingFlight', []);
    $mf            = $bookingFlight['flight'] ?? $bookingFlight;
    $currency  = $mf['currency'] ?? 'NGN';
    $sym = match($currency) { 'NGN' => html_entity_decode('&#8358;', ENT_QUOTES, 'UTF-8'), 'USD' => '$', 'GBP' => html_entity_decode('&pound;', ENT_QUOTES, 'UTF-8'), 'EUR' => html_entity_decode('&euro;', ENT_QUOTES, 'UTF-8'), default => $currency . ' ' };
    $fmt       = fn($v) => $sym . number_format((float)$v, 2);
    $segments  = $mf['segments']       ?? [];
    $retSegs   = $mf['returnSegments'] ?? [];
    $multiLegs = $mf['multiLegs']      ?? [];
    $isReturn  = count($retSegs) > 0;
    $isMulti   = count($multiLegs) > 0;
    $tripLabel = $isReturn ? 'Round Trip' : ($isMulti ? 'Multi-City' : 'One Way');
    $firstSeg  = $segments[0] ?? [];
    $finalDest = $isReturn && !empty($retSegs) ? $retSegs[count($retSegs)-1] : (!empty($segments) ? $segments[count($segments)-1] : []);
    $breakdown = $bookingFlight['fareBreakdown'] ?? $mf['fareBreakdown'] ?? [];
    $contact   = session('bookingContact', []);
    $passengers= session('bookingPassengers', []);
    $passengers = \App\Support\FlightDisplay::passengers($passengers);
    $cabinLabel = \App\Support\FlightDisplay::cabin($mf, $dbBooking ?? null);
    $total     = (float)($mf['price'] ?? 0);
    $uniqueId  = session('bookingUniqueId', '');
    $tktLimit  = session('bookingTktTimeLimit', '');
    $tfPlan    = session('travelFlexPlan', []);
    $downPayment   = (float)($tfPlan['down_payment']   ?? 0);
    $downPercent   = (int)  ($tfPlan['down_percent']   ?? 30);
    $repaymentPlan = $tfPlan['repayment_plan']          ?? '';
    $grandTotal    = (float)($tfPlan['grand_total']    ?? 0);
    $totalInterest = (float)($tfPlan['total_interest'] ?? 0);
    $administrationFee = (float)($tfPlan['administration_fee'] ?? 0);
    $insuranceFee = (float)($tfPlan['insurance_fee'] ?? 0);
    $upfrontPaymentTotal = (float)($tfPlan['upfront_payment_total'] ?? ($downPayment + $administrationFee + $insuranceFee));
    $schedule      = $tfPlan['schedule']               ?? [];
    $ticketCost    = (float) ($tfPlan['ticket_cost'] ?? $total);
    $remainingBal  = (float) ($tfPlan['loan_amount'] ?? $tfPlan['remaining_balance'] ?? max(0, $ticketCost - $downPayment));
    $tktFmt = ''; $tktHours = 0;
    if ($tktLimit) { try { $td=\Carbon\Carbon::parse($tktLimit); $tktFmt=$td->timezone('Africa/Lagos')->format('D, d M Y \a\t H:i'); $tktHours=max(0,(int)now()->diffInHours($td,false)); } catch (\Throwable $e) {} }
    $equipMap = ['73H'=>'Boeing 737-800','738'=>'Boeing 737-800','320'=>'Airbus A320','321'=>'Airbus A321','789'=>'Boeing 787-9','332'=>'Airbus A330-200'];
    $routeLines = [];
    if ($isMulti) {
        foreach ($multiLegs as $li => $leg) {
            $routeLines[] = [
                'label' => 'Leg ' . ($li + 1),
                'route' => ($leg['from'] ?? '') . ' -> ' . ($leg['to'] ?? ''),
                'date'  => $leg['departDateLabel'] ?? '',
            ];
        }
    }

    // Extra Services
    $dbId          = session('flightBookingDbId');
    $dbBooking     = $dbId ? \App\Models\FlightBooking::find($dbId) : null;
    $extraServices = $dbBooking?->extra_services_snapshot ?? [];
    $extrasTotal   = 0.0;
    if (!empty($extraServices)) {
        if (!empty($extraServices['baggage'])) {
            foreach ($extraServices['baggage'] as $item) {
                $extrasTotal += (float) ($item['line_total'] ?? 0);
            }
        }
        if (!empty($extraServices['meal'])) {
            foreach ($extraServices['meal'] as $item) {
                $extrasTotal += (float) ($item['unit_price'] ?? 0);
            }
        }
    }
@endphp

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
@include('livewire.pages.flight.partials._shared_styles');
<style>

    .tf-pnd-hero { background:linear-gradient(135deg,#1e3a5f,var(--indigo),var(--purple)); border-radius:18px; padding:28px; margin-bottom:22px; color:#fff; display:flex; align-items:flex-start; gap:18px; }
    .tf-pnd-hero-icon { font-size:48px; flex-shrink:0; }
    .schedule-table { width:100%; border-collapse:collapse; font-size:12.5px; }
    .schedule-table th { padding:8px 12px; text-align:left; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--gray-400); background:var(--gray-50); border-bottom:1px solid var(--gray-200); }
    .schedule-table td { padding:10px 12px; border-bottom:1px solid var(--gray-100); }
    .schedule-table tr:last-child td { border-bottom:none; }
    .loan-bar { display:flex; background:var(--navy); border-radius:12px; overflow:hidden; }
    .loan-bar-item { flex:1; padding:12px 14px; border-right:1px solid rgba(255,255,255,.08); text-align:center; }
    .loan-bar-item:last-child { border-right:none; }
    .loan-bar-lbl { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:rgba(255,255,255,.55); margin-bottom:3px; }
    .loan-bar-val { font-size:14px; font-weight:800; color:#fff; font-family:var(--mono); }
</style>
<style>
    :root {
        --tf-brand:#39328f;
        --tf-brand-700:#2f287c;
        --tf-green:#049a63;
        --tf-green-soft:#eefaf4;
        --tf-ink:#101828;
        --tf-muted:#667085;
        --tf-subtle:#98a2b3;
        --tf-line:#e6e9f0;
        --tf-soft:#f7f8fb;
        --navy:var(--tf-ink);
        --blue:var(--tf-brand);
        --blue-lt:#f5f7ff;
        --blue-md:rgba(57,50,143,.16);
        --indigo:var(--tf-brand);
        --purple:var(--tf-brand);
        --green:var(--tf-green);
        --green-lt:var(--tf-green-soft);
        --gray-50:var(--tf-soft);
        --gray-100:#eef1f6;
        --gray-200:var(--tf-line);
        --gray-300:#cfd4df;
        --gray-400:var(--tf-subtle);
        --gray-500:var(--tf-muted);
        --gray-700:#344054;
        --gray-900:var(--tf-ink);
    }
    body { background:#f7f8fb; }
    .tf-pnd-hero {
        background:#fff;
        border:1px solid var(--tf-line);
        border-radius:8px;
        color:var(--tf-ink);
        box-shadow:0 14px 36px rgba(16,24,40,.06);
        padding:24px;
    }
    .tf-pnd-hero-icon {
        width:48px;
        height:48px;
        border-radius:999px;
        background:#fff8ed;
        color:#b7791f;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:0;
    }
    .tf-pnd-hero-icon::before {
        content:"";
        width:24px;
        height:24px;
        background:currentColor;
        -webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='5' width='18' height='14' rx='2'/%3E%3Cpath d='m3 7 9 6 9-6'/%3E%3Cpath d='M8 21h8'/%3E%3C/svg%3E") center/contain no-repeat;
        mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='5' width='18' height='14' rx='2'/%3E%3Cpath d='m3 7 9 6 9-6'/%3E%3Cpath d='M8 21h8'/%3E%3C/svg%3E") center/contain no-repeat;
    }
    .tf-pnd-alert-icon {
        width:24px;
        height:24px;
        flex-shrink:0;
        color:#92400e;
        background:currentColor;
        -webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='13' r='8'/%3E%3Cpath d='M12 9v4l2.5 1.5'/%3E%3Cpath d='M5 3 2 6'/%3E%3Cpath d='m22 6-3-3'/%3E%3C/svg%3E") center/contain no-repeat;
        mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='13' r='8'/%3E%3Cpath d='M12 9v4l2.5 1.5'/%3E%3Cpath d='M5 3 2 6'/%3E%3Cpath d='m22 6-3-3'/%3E%3C/svg%3E") center/contain no-repeat;
    }
    .tf-pnd-phone-icon {
        width:14px;
        height:14px;
        display:inline-block;
        vertical-align:-2px;
        margin-right:5px;
        color:var(--tf-muted);
        background:currentColor;
        -webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.91.33 1.8.63 2.65a2 2 0 0 1-.45 2.11L8 9.91a16 16 0 0 0 6 6l1.43-1.29a2 2 0 0 1 2.11-.45c.85.3 1.74.51 2.65.63A2 2 0 0 1 22 16.92Z'/%3E%3C/svg%3E") center/contain no-repeat;
        mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.91.33 1.8.63 2.65a2 2 0 0 1-.45 2.11L8 9.91a16 16 0 0 0 6 6l1.43-1.29a2 2 0 0 1 2.11-.45c.85.3 1.74.51 2.65.63A2 2 0 0 1 22 16.92Z'/%3E%3C/svg%3E") center/contain no-repeat;
    }
    .tf-pnd-kicker {
        display:inline-flex;
        align-items:center;
        gap:7px;
        min-height:30px;
        padding:6px 10px;
        border:1px solid rgba(57,50,143,.16);
        border-radius:999px;
        background:#f5f7ff;
        color:var(--tf-brand);
        font-size:11px;
        font-weight:800;
        margin-bottom:10px;
    }
    .tf-pnd-title { color:var(--tf-ink); font-size:clamp(22px,2.3vw,32px); line-height:1.14; font-weight:800; margin-bottom:8px; }
    .tf-pnd-sub { color:var(--tf-muted); font-size:14px; line-height:1.65; max-width:640px; }
    .tf-pnd-ref {
        display:inline-flex;
        align-items:center;
        gap:8px;
        margin-top:14px;
        padding:8px 12px;
        background:var(--tf-soft);
        border:1px solid var(--tf-line);
        border-radius:8px;
        color:var(--tf-ink);
        font-size:13px;
        font-weight:800;
        font-family:var(--mono);
    }
    .pc, .pg-rail > div { border-radius:8px !important; border-color:var(--tf-line) !important; box-shadow:0 12px 32px rgba(16,24,40,.055) !important; }
    .pc-icon { font-size:0 !important; border-radius:8px !important; background:#f5f7ff !important; color:var(--tf-brand) !important; }
    .pc-icon::before {
        content:"";
        width:18px;
        height:18px;
        background:currentColor;
        -webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='8'/%3E%3Cpath d='M12 8v4l3 2'/%3E%3C/svg%3E") center/contain no-repeat;
        mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='8'/%3E%3Cpath d='M12 8v4l3 2'/%3E%3C/svg%3E") center/contain no-repeat;
    }
    .itin-card-icon::before {
        content:"";
        width:20px;
        height:20px;
        display:inline-block;
        background:currentColor;
        -webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M17.8 19.2 16 11l3.5-3.5C21 6 21 4 19 4c-2 0-2 0-3.5 1.5L12 9 4 7 2 9l7 4-3 3H3l-1 1 4 2 2 4 1-1v-3l3-3 4 7 2-2Z'/%3E%3C/svg%3E") center/contain no-repeat;
        mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M17.8 19.2 16 11l3.5-3.5C21 6 21 4 19 4c-2 0-2 0-3.5 1.5L12 9 4 7 2 9l7 4-3 3H3l-1 1 4 2 2 4 1-1v-3l3-3 4 7 2-2Z'/%3E%3C/svg%3E") center/contain no-repeat;
    }
    .pc-icon.icon-calendar::before { -webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M8 2v4'/%3E%3Cpath d='M16 2v4'/%3E%3Crect x='3' y='4' width='18' height='18' rx='2'/%3E%3Cpath d='M3 10h18'/%3E%3C/svg%3E") center/contain no-repeat; mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M8 2v4'/%3E%3Cpath d='M16 2v4'/%3E%3Crect x='3' y='4' width='18' height='18' rx='2'/%3E%3Cpath d='M3 10h18'/%3E%3C/svg%3E") center/contain no-repeat; }
    .pc-icon.icon-users::before { -webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M16 21v-2a4 4 0 0 0-8 0v2'/%3E%3Ccircle cx='12' cy='7' r='4'/%3E%3Cpath d='M22 21v-2a4 4 0 0 0-3-3.87'/%3E%3Cpath d='M16 3.13a4 4 0 0 1 0 7.75'/%3E%3C/svg%3E") center/contain no-repeat; mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M16 21v-2a4 4 0 0 0-8 0v2'/%3E%3Ccircle cx='12' cy='7' r='4'/%3E%3Cpath d='M22 21v-2a4 4 0 0 0-3-3.87'/%3E%3Cpath d='M16 3.13a4 4 0 0 1 0 7.75'/%3E%3C/svg%3E") center/contain no-repeat; }
    .pc-icon.icon-gift::before { -webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='8' width='18' height='4' rx='1'/%3E%3Cpath d='M12 8v13'/%3E%3Cpath d='M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7'/%3E%3Cpath d='M7.5 8A2.5 2.5 0 1 1 12 6a2.5 2.5 0 1 1 4.5 2'/%3E%3C/svg%3E") center/contain no-repeat; mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='8' width='18' height='4' rx='1'/%3E%3Cpath d='M12 8v13'/%3E%3Cpath d='M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7'/%3E%3Cpath d='M7.5 8A2.5 2.5 0 1 1 12 6a2.5 2.5 0 1 1 4.5 2'/%3E%3C/svg%3E") center/contain no-repeat; }
    .loan-bar { background:#fff; border:1px solid var(--tf-line); border-radius:8px; box-shadow:0 12px 32px rgba(16,24,40,.055); }
    .loan-bar-item { border-right:1px solid #eef1f6; text-align:left; }
    .loan-bar-lbl { color:var(--tf-muted); }
    .loan-bar-val { color:var(--tf-ink); }
    .schedule-table th { background:#fbfcfe; color:var(--tf-muted); }
    .schedule-table td { border-color:#eef1f6; }
    .tf-rail-head-clean { padding:16px 18px; background:var(--tf-ink); }
    .tf-rail-head-clean div { color:#fff; font-size:15px; font-weight:800; }
    @media(max-width:760px) {
        body { margin-top:0 !important; }
        .pg-wrap { padding-top:12px !important; }
        .tf-pnd-hero { flex-direction:column; padding:18px; }
        .pg-grid { display:block !important; width:100% !important; }
        .pg-main, .pg-rail, .pc { width:100% !important; max-width:100% !important; min-width:0 !important; }
        .pc-body { overflow-x:auto; }
        .loan-bar { flex-direction:column; }
        .loan-bar-item { border-right:0; border-bottom:1px solid #eef1f6; }
        .loan-bar-item:last-child { border-bottom:0; }
    }
</style>

<div class="pg-wrap" x-data="{}">

    <div class="tf-pnd-hero">
        <div class="tf-pnd-hero-icon"></div>
        <div>
            <div class="tf-pnd-kicker">TravelFlex Plan</div>
            <div class="tf-pnd-title">Application submitted to Fast Credit</div>
            <div class="tf-pnd-sub">
                Fast Credit will contact you and provide an approval decision within <strong>24 hours</strong>. No down payment is due and no ticket will be issued until the application is approved.
            </div>
            @if($uniqueId)<div class="tf-pnd-ref">{{ $uniqueId }}</div>@endif
        </div>
    </div>

    <div class="pg-grid">
        <div class="pg-main">

            {{-- Timeline --}}
            <div class="pc">
                <div class="pc-head">
                    <div class="pc-icon" style="background:var(--blue-lt);color:var(--blue);"></div>
                    <div><div class="pc-title">What Happens Next</div></div>
                </div>
                <div class="pc-body" style="padding:14px 20px 4px;">
                    @foreach([
                        ['done','1','Application Submitted','Your TravelFlex application, documents, and repayment plan have been received.'],
                        ['current','2','Fast Credit Review','Fast Credit will contact you and provide a decision within 24 hours.'],
                        ['pending','3','Pay Down Payment','If approved, we will email you a secure payment link before the airline hold expires.'],
                        ['pending','4','Ticketing','TravelWheel issues your ticket only after approval and verified down payment.'],
                    ] as [$cls,$num,$title,$sub])
                    <div class="tl-step">
                        <div class="tl-num {{ $cls }}">{{ $num }}</div>
                        <div>
                            <div class="tl-title">{{ $title }}
                                @if($cls==='current')<span style="font-size:10.5px;background:var(--amber-lt);color:var(--amber);padding:2px 7px;border-radius:999px;font-weight:700;margin-left:6px;">In Progress</span>@endif
                            </div>
                            <div class="tl-sub">{{ $sub }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Deadline --}}
            @if($tktFmt)
            <div style="background:var(--amber-lt);border:1px solid #fed7aa;border-radius:12px;padding:14px 18px;display:flex;align-items:flex-start;gap:12px;">
                <span class="tf-pnd-alert-icon" aria-hidden="true"></span>
                <div>
                    <div style="font-size:13px;font-weight:800;color:#92400e;margin-bottom:3px;">Booking Hold Expires</div>
                    <div style="font-size:12.5px;color:#78350f;line-height:1.55;">Your held fare expires <strong>{{ $tktFmt }}</strong>@if($tktHours>0) ({{ $tktHours }}h remaining)@endif. If Fast Credit approves the application, payment must be completed before the secure deadline in your approval email.</div>
                </div>
            </div>
            @endif

            {{-- Loan Summary Bar --}}
            <div class="loan-bar">
                <div class="loan-bar-item"><div class="loan-bar-lbl">Ticket Cost</div><div class="loan-bar-val">{{ $fmt($total) }}</div></div>
                <div class="loan-bar-item"><div class="loan-bar-lbl">Down Payment ({{ $downPercent }}%)</div><div class="loan-bar-val" style="color:#86efac;">{{ $fmt($downPayment) }}</div></div>
                <div class="loan-bar-item"><div class="loan-bar-lbl">Fees</div><div class="loan-bar-val">{{ $fmt($administrationFee + $insuranceFee) }}</div></div>
                <div class="loan-bar-item"><div class="loan-bar-lbl">Balance Due</div><div class="loan-bar-val">{{ $fmt($remainingBal) }}</div></div>
                <div class="loan-bar-item"><div class="loan-bar-lbl">Grand Total</div><div class="loan-bar-val" style="color:#c4b5fd;">{{ $fmt($grandTotal) }}</div></div>
            </div>

            {{-- Flight Itinerary --}}
            <div class="pc">
                <div class="pc-head">
                    <div class="pc-icon itin-card-icon" style="background:var(--blue-lt);color:var(--blue);"></div>
                    <div><div class="pc-title">Flight Itinerary</div><div class="pc-sub">{{ $tripLabel }} &middot; {{ $cabinLabel }} &middot; {{ $mf['airline']??'' }}</div></div>
                </div>
                @if(!$isMulti)
                @include('livewire.pages.flight.partials._render_leg', ['legSegs'=>$segments,'legLabel'=>'Outbound','legBadgeClass'=>'outbound','legLayovers'=>$mf['layoverDurations']??[],'legStops'=>$mf['stops']??max(0,count($segments)-1),'legDuration'=>$mf['totalTimeLabel']??'','legDate'=>$mf['departDateLabel']??'','breakdown'=>$breakdown,'equipMap'=>$equipMap,'tripDetails'=>[]])
                @endif
                @if($isReturn && !empty($retSegs))
                @include('livewire.pages.flight.partials._render_leg', ['legSegs'=>$retSegs,'legLabel'=>'Return','legBadgeClass'=>'inbound','legLayovers'=>$mf['returnLayoverDurations']??[],'legStops'=>$mf['returnStops']??max(0,count($retSegs)-1),'legDuration'=>$mf['returnTotalTimeLabel']??'','legDate'=>$mf['returnDateLabel']??'','breakdown'=>$breakdown,'equipMap'=>$equipMap,'tripDetails'=>[]])
                @endif
                @if($isMulti) @foreach($multiLegs as $li=>$leg) @php $ls=$leg['segments']??[]; @endphp @if(!empty($ls)) @include('livewire.pages.flight.partials._render_leg',['legSegs'=>$ls,'legLabel'=>'Leg '.($li+1),'legBadgeClass'=>'multi','legLayovers'=>$leg['layoverDurations']??[],'legStops'=>$leg['stops']??max(0,count($ls)-1),'legDuration'=>$leg['totalTimeLabel']??'','legDate'=>$leg['departDateLabel']??'','breakdown'=>$breakdown,'equipMap'=>$equipMap,'tripDetails'=>[]]) @endif @endforeach @endif
            </div>

            {{-- Repayment Schedule --}}
            @if(!empty($schedule))
            <div class="pc">
                <div class="pc-head">
                    <div class="pc-icon icon-calendar" style="background:var(--purple-lt);color:var(--purple);"></div>
                    <div><div class="pc-title">Your Repayment Schedule</div><div class="pc-sub">{{ count($schedule) }} instalment(s) &middot; {{ $repaymentPlan }}</div></div>
                </div>
                <div class="pc-body" style="padding:0;">
                    <table class="schedule-table">
                        <thead><tr><th>#</th><th>Instalment</th><th>Due Date</th><th>Principal</th><th>Interest</th><th>Total</th></tr></thead>
                        <tbody>
                            @foreach($schedule as $i=>$inst)
                            <tr>
                                <td style="color:var(--gray-400);font-weight:700;">{{ $i+1 }}</td>
                                <td><strong>{{ $inst['label'] ?? (($i+1).'. Payment') }}</strong></td>
                                <td><span style="padding:2px 8px;background:var(--blue-lt);color:var(--blue);border-radius:999px;font-size:10.5px;font-weight:700;">{{ $inst['dueDate'] ?? '-' }}</span></td>
                                <td style="font-family:var(--mono);">{{ $fmt($inst['principal']??0) }}</td>
                                <td style="font-family:var(--mono);color:var(--amber);">{{ $fmt($inst['interest']??0) }}</td>
                                <td><strong style="font-family:var(--mono);color:var(--indigo);">{{ $fmt($inst['total']??0) }}</strong></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Passengers --}}
            @if(!empty($passengers))
            <div class="pc">
                <div class="pc-head"><div class="pc-icon icon-users" style="background:#f0f9ff;color:#0369a1;"></div><div><div class="pc-title">Passengers</div></div></div>
                <div class="pc-body" style="padding:0;">
                    <table class="pax-table">
                        <thead><tr><th>#</th><th>Name</th><th>Type</th><th>DOB</th><th>Nationality</th><th>Passport</th></tr></thead>
                        <tbody>
                            @foreach($passengers as $i=>$pax)
                            @php $c=match($pax['type']??'ADT'){'ADT'=>['#dbeafe','#1d4ed8'],'CHD'=>['#fef3c7','#d97706'],'INF'=>['#f0fdf4','#059669'],default=>['#f1f5f9','#64748b']}; @endphp
                            <tr>
                                <td style="color:var(--gray-400)">{{ $i+1 }}</td>
                                <td><strong>{{ $pax['title']??'' }} {{ strtoupper($pax['first_name']??'') }} {{ strtoupper($pax['last_name']??'') }}</strong></td>
                                <td><span class="pax-badge" style="background:{{$c[0]}};color:{{$c[1]}}">{{ match($pax['type']??'ADT'){'ADT'=>'Adult','CHD'=>'Child','INF'=>'Infant',default=>'Pax'} }}</span></td>
                                <td>{{ !empty($pax['dob']) ? \Carbon\Carbon::parse($pax['dob'])->format('d M Y') : '-' }}</td>
                                <td>{{ $pax['nationality'] ?? '-' }}</td>
                                <td style="font-family:var(--mono);font-size:12px;">{{ $pax['passport_no'] ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Extra Services (TravelFlex) --}}
            @if(!empty($extraServices['baggage']) || !empty($extraServices['meal']))
            <div class="pc">
                <div class="pc-head">
                    <div class="pc-icon icon-gift" style="background:#fef3c7;color:#d97706;"></div>
                    <div>
                        <div class="pc-title">Extra Services</div>
                        <div class="pc-sub">Included in TravelFlex payment plan</div>
                    </div>
                </div>
                <div class="pc-body" style="padding:0;">
                    <table class="pax-table">
                        <thead><tr><th>Service</th><th>Details</th><th>Price</th></tr></thead>
                        <tbody>
                            @if(!empty($extraServices['baggage']))
                                @foreach($extraServices['baggage'] as $baggage)
                                <tr>
                                    <td><strong style="color:#059669;">Extra Baggage</strong></td>
                                    <td>
                                        {{ $baggage['description'] ?? '' }}
                                        <span style="font-size:11px;color:var(--gray-400);display:block;margin-top:2px;">{{ ucfirst($baggage['direction'] ?? '') }} &middot; Qty: {{ $baggage['quantity'] ?? 1 }}</span>
                                    </td>
                                    <td style="font-weight:700;color:#0f172a;">{{ $sym }}{{ number_format((float)($baggage['line_total'] ?? 0), 2) }}</td>
                                </tr>
                                @endforeach
                            @endif
                            @if(!empty($extraServices['meal']))
                                @foreach($extraServices['meal'] as $meal)
                                <tr>
                                    <td><strong style="color:#d97706;">Meal</strong></td>
                                    <td>
                                        {{ $meal['description'] ?? '' }}
                                        <span style="font-size:11px;color:var(--gray-400);display:block;margin-top:2px;">{{ ucfirst($meal['direction'] ?? '') }} &middot; Segment {{ ($meal['segment'] ?? 0) + 1 }}</span>
                                    </td>
                                    <td style="font-weight:700;color:#0f172a;">{{ $sym }}{{ number_format((float)($meal['unit_price'] ?? 0), 2) }}</td>
                                </tr>
                                @endforeach
                            @endif
                        </tbody>
                        <tfoot>
                            <tr style="background:#fef3c7;">
                                <td colspan="2" style="padding:10px 14px;font-weight:700;color:#d97706;">Total Extras</td>
                                <td style="padding:10px 14px;font-weight:800;color:#d97706;font-size:14px;">{{ $fmt($extrasTotal) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @endif

            <div class="notice purple">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span>Repayment schedule begins from plan activation date. You'll receive reminder emails 3 days before each due date. Missed payments may result in cancellation.</span>
            </div>

            <div style="display:flex;gap:12px;flex-wrap:wrap;" class="btn-row">
                <a href="{{ route('home') }}" class="btn-primary" style="background:linear-gradient(135deg,var(--indigo),var(--purple));">Back to Home</a>
                <a href="#" onclick="window.print()" class="btn-ghost">Print / Save</a>
            </div>
        </div>

        {{-- RAIL --}}
        <aside class="pg-rail">
            <div class="pc">
                <div style="padding:14px 18px;background:linear-gradient(135deg,var(--navy),var(--indigo),var(--purple));">
                    <div style="font-size:15px;font-weight:800;color:#fff;">TravelFlex Summary</div>
                </div>
                <div class="pc-body">
                    <div class="dr"><span class="dr-lbl">Route</span><span class="dr-val">@if($isMulti)@foreach($routeLines as $line)<div>{{ $line['route'] }}</div>@endforeach @else {{ ($firstSeg['from']??'') }} -> {{ ($finalDest['to']??'') }} @endif</span></div>
                    <div class="dr"><span class="dr-lbl">Trip Type</span><span class="dr-val">{{ $tripLabel }}</span></div>
                    @if($isReturn && !empty($mf['returnDateLabel']))<div class="dr"><span class="dr-lbl">Return</span><span class="dr-val">{{ $mf['returnDateLabel'] }}</span></div>@endif
                    @if($uniqueId)<div class="dr"><span class="dr-lbl">Booking Ref</span><span class="dr-val mono">{{ $uniqueId }}</span></div>@endif
                    <div class="dr"><span class="dr-lbl">Due after approval</span><span class="dr-val" style="color:var(--green);">{{ $fmt($upfrontPaymentTotal) }}</span></div>
                    <div class="dr"><span class="dr-lbl">Down payment</span><span class="dr-val">{{ $fmt($downPayment) }} ({{ $downPercent }}%)</span></div>
                    <div class="dr"><span class="dr-lbl">Admin + insurance</span><span class="dr-val">{{ $fmt($administrationFee + $insuranceFee) }}</span></div>
                    <div class="dr"><span class="dr-lbl">Balance</span><span class="dr-val">{{ $fmt($remainingBal) }}</span></div>
                    <div class="dr"><span class="dr-lbl">Repayment</span><span class="dr-val">{{ $repaymentPlan }}</span></div>
                    <div class="dr"><span class="dr-lbl">Status</span><span class="dr-val"><span class="status-badge status-pending" style="font-size:10px;">Pending</span></span></div>
                </div>
                <div class="fare-total"><span class="fare-total-lbl">Grand Total</span><span class="fare-total-val">{{ $fmt($grandTotal) }}</span></div>
            </div>
            <div style="background:#fff;border:1px solid var(--gray-200);border-radius:var(--radius);padding:16px 18px;box-shadow:var(--shadow-sm);">
                <div style="font-size:13px;font-weight:800;color:var(--gray-900);margin-bottom:8px;">Need Help?</div>
                <div style="font-size:12.5px;color:var(--gray-500);line-height:1.65;">
                    <a href="mailto:support@travelwheel.com" style="color:var(--blue);font-weight:600;">support@travelwheel.com</a><br>
                    <span class="tf-pnd-phone-icon" aria-hidden="true"></span><strong>+234 800 000 0000</strong><br>
                    Quote: <strong style="font-family:var(--mono);color:var(--navy);">{{ $uniqueId }}</strong>
                </div>
            </div>
        </aside>
    </div>
</div>
<script src="//unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endcomponent
