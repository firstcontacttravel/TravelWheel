{{-- resources/views/livewire/pages/flight/flight-travelflex-confirmation.blade.php --}}
@component('layouts.app', ['title' => 'TravelFlex — Plan Activated!'])

@php
    // ── Core data ──────────────────────────────────────────────────────────────
    $bookingFlight = session('bookingFlight', []);
    $mf            = $bookingFlight['flight'] ?? $bookingFlight;

    $currency  = $mf['currency'] ?? 'NGN';
    $sym       = match($currency) { 'NGN' => '₦', 'USD' => '$', 'GBP' => '£', 'EUR' => '€', default => $currency.' ' };
    $fmt       = fn($v) => $sym . number_format((float)$v, 2);

    $segments  = $mf['segments']        ?? [];
    $retSegs   = $mf['returnSegments']  ?? [];
    $multiLegs = $mf['multiLegs']       ?? [];
    $isReturn  = count($retSegs) > 0;
    $isMulti   = count($multiLegs) > 0;
    $tripLabel = $isReturn ? 'Round Trip' : ($isMulti ? 'Multi-City' : 'One Way');

    $firstSeg  = $segments[0] ?? [];
    $lastSeg   = !empty($segments) ? $segments[count($segments)-1] : [];
    $finalDest = $isReturn && !empty($retSegs) ? $retSegs[count($retSegs)-1] : $lastSeg;

    $breakdown  = $bookingFlight['fareBreakdown'] ?? $mf['fareBreakdown'] ?? [];
    $contact    = session('bookingContact', []);
    $passengers = session('bookingPassengers', []);
    $total      = (float)($mf['price'] ?? 0);
    $uniqueId   = session('bookingUniqueId', '');
    $bookingRef    = $bookingRef ?? session('bookingRef', $dbBooking?->booking_ref ?? '');

    // TravelFlex plan
    $tfPlan        = session('travelFlexPlan', []);
    $downPayment   = (float) ($tfPlan['down_payment']   ?? 0);
    $downPercent   = (int)   ($tfPlan['down_percent']   ?? 30);
    $repaymentPlan = $tfPlan['repayment_plan']           ?? '';
    $grandTotal    = (float) ($tfPlan['grand_total']    ?? 0);
    $totalInterest = (float) ($tfPlan['total_interest'] ?? 0);
    $schedule      = $tfPlan['schedule']                 ?? [];
    $remainingBal  = $total - $downPayment;

    // Live trip details (fetched by controller after ticketing)
    $tripDetails   = $tripDetails ?? [];
    $resItems      = collect(data_get($tripDetails, 'ItineraryInfo.ReservationItems', []))->map(fn($r) => $r['ReservationItem'] ?? $r);
    $customerInfos = collect(data_get($tripDetails, 'ItineraryInfo.CustomerInfos', []))->map(fn($c) => $c['CustomerInfo'] ?? $c);
    $bookingStatus = $tripDetails['BookingStatus'] ?? 'Confirmed';
    $ticketStatus  = $tripDetails['TicketStatus']  ?? '';
    $pnrMap        = [];
    foreach ($resItems as $ri) {
        $pnrMap[($ri['MarketingAirlineCode']??'').($ri['FlightNumber']??'')] = $ri['AirlinePNR'] ?? '';
    }
    $eticketMap = [];
    foreach ($customerInfos as $c) {
        if (!empty($c['eTicketNumber'])) $eticketMap[$c['ItemRPH']] = $c['eTicketNumber'];
    }

    $equipMap = [
        '73H'=>'Boeing 737-800','738'=>'Boeing 737-800','7M8'=>'Boeing 737 MAX 8',
        '789'=>'Boeing 787-9','788'=>'Boeing 787-8','320'=>'Airbus A320',
        '321'=>'Airbus A321','332'=>'Airbus A330-200','333'=>'Airbus A330-300',
        'E90'=>'Embraer E190','73W'=>'Boeing 737-700','77W'=>'Boeing 777-300ER',
    ];
    $routeLines = [];
    if ($isMulti) {
        foreach ($multiLegs as $li => $leg) {
            $routeLines[] = [
                'label' => 'Leg ' . ($li + 1),
                'route' => ($leg['from'] ?? '') . ' → ' . ($leg['to'] ?? ''),
                'date'  => $leg['departDateLabel'] ?? '',
            ];
        }
    }

    // ── UPDATE: Status detection ──
    $ticketStatus = $tripDetails['TicketStatus'] ?? 'UNKNOWN';
    $bookingStatus = $tripDetails['BookingStatus'] ?? 'Booked';
    $isTicketed = strtoupper($ticketStatus) === 'TICKETED';
    $isConfirmedOnly = strtoupper($bookingStatus) === 'CONFIRMED' && !$isTicketed;

    // ── Extra Services ──────────────────────────────────────────────────────
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
    
  

    /* ── TravelFlex hero ── */
    .tf-hero { 
        background: var(--navy); /* Direct navy color instead of var */
        border-radius: 18px; 
        padding: 32px 28px; 
        margin-bottom: 24px; 
        display: flex; 
        align-items: flex-start; 
        gap: 22px; 
        position: relative; 
        overflow: hidden; 
    }
        .tf-hero::before { content:''; position:absolute; top:-80px; right:-80px; width:320px; height:320px; background:radial-gradient(circle,rgba(255,255,255,.12) 0%,transparent 70%); pointer-events:none; }
    .tf-hero-icon  { width:72px; height:72px; border-radius:50%; background:rgba(255,255,255,.15); display:flex; align-items:center; justify-content:center; font-size:34px; flex-shrink:0; position:relative; z-index:2; }
    .tf-hero-title { font-size:24px; font-weight:800; color:#fff; margin-bottom:6px; }
    .tf-hero-sub   { font-size:13.5px; color:rgba(255,255,255,.85); line-height:1.65; max-width:500px; }
    .tf-hero-ref   { display:inline-flex; align-items:center; gap:10px; margin-top:14px; padding:10px 18px; background:rgba(255,255,255,.15); border-radius:10px; color:#fff; font-family:var(--mono); }
    .tf-hero-ref-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; opacity:.7; }
    .tf-hero-ref-val   { font-size:18px; font-weight:800; letter-spacing:.04em; }

    /* ── Schedule table ── */
    .schedule-table { width:100%; border-collapse:collapse; font-size:13px; }
    .schedule-table th { padding:9px 14px; text-align:left; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--gray-400); background:var(--gray-50); border-bottom:1px solid var(--gray-200); }
    .schedule-table td { padding:12px 14px; border-bottom:1px solid var(--gray-100); vertical-align:middle; }
    .schedule-table tr:last-child td { border-bottom:none; }
    .schedule-table tr:nth-child(even) td { background:#fafbff; }

    /* ── Loan summary bar ── */
    .loan-bar { display:flex; align-items:stretch; gap:0; background:var(--navy); border-radius:12px; overflow:hidden; margin-bottom:0; }
    .loan-bar-item { flex:1; padding:14px 16px; border-right:1px solid rgba(255,255,255,.08); text-align:center; }
    .loan-bar-item:last-child { border-right:none; }
    .loan-bar-lbl { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:rgba(255,255,255,.55); margin-bottom:4px; }
    .loan-bar-val { font-size:16px; font-weight:800; color:#fff; font-family:var(--mono); }

    /* ── Itin visual ── */
    .itin-visual { display:flex; align-items:center; justify-content:space-between; gap:8px; padding:16px 20px; background:var(--gray-50); border-bottom:1px solid var(--gray-100); flex-wrap:wrap; }
    .itin-iata { font-size:28px; font-weight:800; color:var(--navy); font-family:var(--mono); }
    .itin-city { font-size:11px; color:var(--gray-400); margin-top:2px; }
    .itin-line { flex:1; height:1px; background:var(--gray-300); max-width:80px; }

    /* ── Upcoming badge ── */
    .upcoming-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:999px; font-size:10.5px; font-weight:700; background:var(--amber-lt); color:var(--amber); }
</style>

<div class="pg-wrap" x-data="{}">

    {{-- ── Hero ── --}}
    <div class="tf-hero">
        <div class="tf-hero-icon">{{ $isTicketed ? '🎉' : '⏳' }}</div>
        <div style="position:relative;z-index:2;flex:1;">
            <div style="display:inline-flex;align-items:center;gap:6px;padding:3px 10px;background:rgba(255,255,255,.15);border-radius:999px;font-size:11px;font-weight:700;color:rgba(255,255,255,.85);margin-bottom:10px;">
                {{ $isTicketed ? '✅ TravelFlex Plan Activated' : '⏳ TravelFlex Plan Activated (Ticketing)' }}
            </div>
            <div class="tf-hero-title">
                {{ $isTicketed ? 'Flight Booked & TravelFlex Plan Live!' : 'TravelFlex Activated — E-Ticket Processing' }}
            </div>
            <div class="tf-hero-sub">
                @if($isTicketed)
                    Your down payment has been processed. Your seat is confirmed and your e-ticket has been issued.
                    Stick to your repayment schedule to keep your booking active.
                    All details have been sent to <strong style="color: white;">{{ $contact['email'] ?? '' }}</strong>.
                @else
                    Your TravelFlex plan is now active and your down payment has been processed. Your seat is confirmed.
                    Your e-ticket is being processed and will be emailed to <strong style="color:white;">{{ $contact['email'] ?? '' }}</strong>
                    within 15–30 minutes. Your repayment schedule is locked in.
                @endif
            </div>
            @if($uniqueId)
            <div class="tf-hero-ref">
                <div>
                    <div class="tf-hero-ref-label" style="color: white;">Booking Reference</div>
                    <div class="tf-hero-ref-val" style="color: white;">{{ $bookingRef }}</div>
                </div>
                <span class="status-badge status-confirmed" style="font-size:11px;">
                    {{ $isTicketed ? '✓ Ticketed' : '⏳ Confirmed' }}
                </span>
            </div>
            @endif
        </div>
    </div>

    @if($errors->has('error'))
    <div class="notice" style="background:var(--red-lt);color:var(--red);border:1px solid #fca5a5;border-radius:12px;margin-bottom:20px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <div>{{ $errors->first('error') }}</div>
    </div>
    @endif

    <div class="pg-grid">
        <div class="pg-main">

            {{-- ── Loan Summary Bar ── --}}
            <div class="loan-bar">
                <div class="loan-bar-item">
                    <div class="loan-bar-lbl">Ticket Cost</div>
                    <div class="loan-bar-val">{{ $fmt($total) }}</div>
                </div>
                <div class="loan-bar-item">
                    <div class="loan-bar-lbl">Down Paid ({{ $downPercent }}%)</div>
                    <div class="loan-bar-val" style="color:#86efac;">{{ $fmt($downPayment) }}</div>
                </div>
                <div class="loan-bar-item">
                    <div class="loan-bar-lbl">Balance Due</div>
                    <div class="loan-bar-val">{{ $fmt($remainingBal) }}</div>
                </div>
                <div class="loan-bar-item">
                    <div class="loan-bar-lbl">Total Payable</div>
                    <div class="loan-bar-val" style="color:#c4b5fd;">{{ $fmt($grandTotal) }}</div>
                </div>
            </div>

            {{-- ── UPDATE: Ticketing status alert ── --}}
            @if($isConfirmedOnly)
            <div class="notice amber" style="background:var(--amber-lt);border:1px solid var(--amber-md);border-radius:12px;margin-bottom:20px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="color:var(--amber);flex-shrink:0;"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
                <div>
                    <div style="font-weight:700;color:var(--amber-dark);margin-bottom:3px;">🎫 E-Ticket Processing</div>
                    <div style="font-size:13px;color:var(--amber-dark);line-height:1.6;">
                        Your booking is confirmed and your seat is reserved. Your e-ticket is being processed and will be emailed to <strong>{{ $contact['email'] ?? '' }}</strong> shortly (usually within 15–30 minutes).
                        <br><br>
                        Your TravelFlex repayment schedule is active. Check your email (including spam folder) for your ticket. If you don't receive it within 1 hour, contact support with your booking reference.
                    </div>
                </div>
            </div>
            @endif

            {{-- ── Flight Itinerary ── --}}
            <div class="pc">
                <div class="pc-head">
                    <div class="pc-icon" style="background:var(--blue-lt);color:var(--blue);">✈️</div>
                    <div>
                        <div class="pc-title">Flight Itinerary</div>
                        <div class="pc-sub">{{ $tripLabel }} · {{ $mf['cabin'] ?? 'Economy' }} · {{ $mf['airline'] ?? '' }}</div>
                    </div>
                </div>

                <div class="itin-visual">
                    <div style="text-align:center;">
                        <div class="itin-iata">{{ $firstSeg['from'] ?? '' }}</div>
                        <div class="itin-city">{{ $firstSeg['fromCity'] ?? '' }}</div>
                    </div>
                    <div style="flex:1;display:flex;align-items:center;gap:6px;justify-content:center;">
                        <div class="itin-line"></div>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="1.5" stroke-linecap="round"><path d="M17.8 19.2L16 11l3.5-3.5C21 6 21 4 19 4c-2 0-2 0-3.5 1.5L11 9l-8.2-1.8A1 1 0 0 0 2 8.05L3.95 11a1 1 0 0 0 .67.45L11 13l-1 4.5a1 1 0 0 0 .28.95L12 20a1 1 0 0 0 1.6-.35l1.54-3.81"/></svg>
                        <div class="itin-line"></div>
                    </div>
                    <div style="text-align:center;">
                        <div class="itin-iata">{{ $finalDest['to'] ?? '' }}</div>
                        <div class="itin-city">{{ $finalDest['toCity'] ?? '' }}</div>
                    </div>
                </div>

                {{-- Outbound --}}
                @if(!$isMulti)
                @include('livewire.pages.flight.partials._render_leg', [
                    'legSegs' => $segments, 'legLabel' => 'Outbound', 'legBadgeClass' => 'outbound',
                    'legLayovers' => $mf['layoverDurations'] ?? [],
                    'legStops' => $mf['stops'] ?? max(0, count($segments)-1),
                    'legDuration' => $mf['totalTimeLabel'] ?? '', 'legDate' => $mf['departDateLabel'] ?? '',
                    'breakdown' => $breakdown, 'equipMap' => $equipMap, 'tripDetails' => $tripDetails,
                ])
                @endif

                {{-- Return --}}
                @if($isReturn && !empty($retSegs))
                @include('livewire.pages.flight.partials._render_leg', [
                    'legSegs' => $retSegs, 'legLabel' => 'Return', 'legBadgeClass' => 'inbound',
                    'legLayovers' => $mf['returnLayoverDurations'] ?? [],
                    'legStops' => $mf['returnStops'] ?? max(0, count($retSegs)-1),
                    'legDuration' => $mf['returnTotalTimeLabel'] ?? '', 'legDate' => $mf['returnDateLabel'] ?? '',
                    'breakdown' => $breakdown, 'equipMap' => $equipMap, 'tripDetails' => $tripDetails,
                ])
                @endif

                {{-- Multi-city --}}
                @if($isMulti)
                    @foreach($multiLegs as $li => $leg)
                        @php $legSegs = $leg['segments'] ?? []; @endphp
                        @if(!empty($legSegs))
                        @include('livewire.pages.flight.partials._render_leg', [
                            'legSegs' => $legSegs, 'legLabel' => 'Leg '.($li+1), 'legBadgeClass' => 'multi',
                            'legLayovers' => $leg['layoverDurations'] ?? [],
                            'legStops' => $leg['stops'] ?? max(0, count($legSegs)-1),
                            'legDuration' => $leg['totalTimeLabel'] ?? '', 'legDate' => $leg['departDateLabel'] ?? '',
                            'breakdown' => $breakdown, 'equipMap' => $equipMap, 'tripDetails' => $tripDetails,
                        ])
                        @endif
                    @endforeach
                @endif
            </div>

            {{-- ── UPDATE: E-Tickets (conditional rendering) ── --}}
            @if($isTicketed && !empty($eticketMap))
            <div class="pc">
                <div class="pc-head">
                    <div class="pc-icon" style="background:var(--indigo-lt);color:var(--indigo);">🎫</div>
                    <div>
                        <div class="pc-title">E-Ticket Numbers</div>
                        <div class="pc-sub">Present these at airport check-in</div>
                    </div>
                </div>
                <div class="pc-body">
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px;">
                        @foreach($customerInfos as $c)
                            @if(!empty($c['eTicketNumber']))
                            <div style="background:var(--indigo-lt);border:1px solid #c7d2fe;border-radius:10px;padding:12px 14px;">
                                <div style="font-size:12px;font-weight:700;color:var(--indigo);margin-bottom:4px;">{{ $c['PassengerTitle']??'' }} {{ $c['PassengerFirstName']??'' }} {{ $c['PassengerLastName']??'' }}</div>
                                <div style="font-size:14px;font-weight:800;color:var(--navy);font-family:var(--mono);">{{ $c['eTicketNumber'] }}</div>
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
            @elseif(!$isTicketed)
            {{-- Show placeholder when not yet ticketed --}}
            <div class="pc">
                <div class="pc-head">
                    <div class="pc-icon" style="background:var(--gray-lt);color:var(--gray-400);">🎫</div>
                    <div>
                        <div class="pc-title">E-Ticket Numbers</div>
                        <div class="pc-sub">Will appear here once issued</div>
                    </div>
                </div>
                <div class="pc-body">
                    <div style="text-align:center;padding:20px;color:var(--gray-400);">
                        <div style="font-size:32px;margin-bottom:8px;">⏳</div>
                        <div style="font-size:13px;font-weight:600;color:var(--gray-500);">Your e-tickets are being processed</div>
                        <div style="font-size:12px;color:var(--gray-400);margin-top:6px;line-height:1.6;">
                            This typically takes 15–30 minutes. Check your email at <strong>{{ $contact['email'] ?? 'your registered email' }}</strong> for updates.
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- ── Repayment Schedule ── --}}
            @if(!empty($schedule))
            <div class="pc">
                <div class="pc-head">
                    <div class="pc-icon" style="background:var(--purple-lt);color:var(--purple);">📅</div>
                    <div>
                        <div class="pc-title">Your Repayment Schedule</div>
                        <div class="pc-sub">{{ count($schedule) }} instalment(s) · {{ $repaymentPlan }} · 5% interest per period</div>
                    </div>
                </div>
                <div class="pc-body" style="padding:0;">
                    <table class="schedule-table">
                        <thead>
                            <tr><th>#</th><th>Instalment</th><th>Due Date</th><th>Principal</th><th>Interest (5%)</th><th>Total Due</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @foreach($schedule as $i => $inst)
                            <tr>
                                <td style="color:var(--gray-400);font-weight:700;">{{ $i+1 }}</td>
                                <td><strong>{{ $inst['label'] ?? (($i+1).'. Payment') }}</strong></td>
                                <td>
                                    <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;background:var(--blue-lt);color:var(--blue);border-radius:999px;font-size:10.5px;font-weight:700;">
                                        📅 {{ $inst['dueDate'] ?? '—' }}
                                    </span>
                                </td>
                                <td style="font-family:var(--mono);">{{ $fmt($inst['principal'] ?? 0) }}</td>
                                <td style="font-family:var(--mono);color:var(--amber);">{{ $fmt($inst['interest'] ?? 0) }}</td>
                                <td><strong style="font-family:var(--mono);color:var(--indigo);">{{ $fmt($inst['total'] ?? 0) }}</strong></td>
                                <td><span class="upcoming-badge">Upcoming</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="background:var(--gray-50);">
                                <td colspan="4" style="padding:10px 14px;font-weight:700;font-size:12px;color:var(--gray-500);">Total Interest: {{ $fmt($totalInterest) }}</td>
                                <td colspan="3" style="padding:10px 14px;text-align:right;">
                                    <span style="font-size:14px;font-weight:800;color:var(--navy);font-family:var(--mono);">Grand Total: {{ $fmt($grandTotal) }}</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @endif

            {{-- ── Passengers ── --}}
            @if(!empty($passengers))
            <div class="pc">
                <div class="pc-head">
                    <div class="pc-icon" style="background:#f0f9ff;color:#0369a1;">👥</div>
                    <div><div class="pc-title">Passengers ({{ count($passengers) }})</div></div>
                </div>
                <div class="pc-body" style="padding:0;">
                    <table class="pax-table">
                        <thead><tr><th>#</th><th>Name</th><th>Type</th><th>DOB</th><th>Nationality</th><th>Passport</th></tr></thead>
                        <tbody>
                            @foreach($passengers as $i => $pax)
                            @php $c = match($pax['type']??'ADT'){'ADT'=>['#dbeafe','#1d4ed8'],'CHD'=>['#fef3c7','#d97706'],'INF'=>['#f0fdf4','#059669'],default=>['#f1f5f9','#64748b']}; @endphp
                            <tr>
                                <td style="color:var(--gray-400)">{{ $i+1 }}</td>
                                <td><strong>{{ $pax['title']??'' }} {{ strtoupper($pax['first_name']??'') }} {{ strtoupper($pax['last_name']??'') }}</strong></td>
                                <td><span class="pax-badge" style="background:{{$c[0]}};color:{{$c[1]}}">{{ match($pax['type']??'ADT'){'ADT'=>'Adult','CHD'=>'Child','INF'=>'Infant',default=>'Pax'} }}</span></td>
                                <td>{{ !empty($pax['dob']) ? \Carbon\Carbon::parse($pax['dob'])->format('d M Y') : '—' }}</td>
                                <td>{{ $pax['nationality'] ?? '—' }}</td>
                                <td style="font-family:var(--mono);font-size:12px;">{{ $pax['passport_no'] ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- ── Extra Services (TravelFlex) ── --}}
            @if(!empty($extraServices['baggage']) || !empty($extraServices['meal']))
            <div class="pc">
                <div class="pc-head">
                    <div class="pc-icon" style="background:#fef3c7;color:#d97706;">🎁</div>
                    <div>
                        <div class="pc-title">Extra Services</div>
                        <div class="pc-sub">Selected baggage & meals included in TravelFlex plan</div>
                    </div>
                </div>
                <div class="pc-body" style="padding:0;">
                    <table class="pax-table">
                        <thead><tr><th>Service</th><th>Details</th><th>Price</th></tr></thead>
                        <tbody>
                            @if(!empty($extraServices['baggage']))
                                @foreach($extraServices['baggage'] as $baggage)
                                <tr>
                                    <td><strong style="color:#059669;">🧳 Extra Baggage</strong></td>
                                    <td>
                                        {{ $baggage['description'] ?? '' }}
                                        <span style="font-size:11px;color:var(--gray-400);display:block;margin-top:2px;">{{ ucfirst($baggage['direction'] ?? '') }} · Qty: {{ $baggage['quantity'] ?? 1 }}</span>
                                    </td>
                                    <td style="font-weight:700;color:#0f172a;">{{ $sym }}{{ number_format((float)($baggage['line_total'] ?? 0), 2) }}</td>
                                </tr>
                                @endforeach
                            @endif
                            @if(!empty($extraServices['meal']))
                                @foreach($extraServices['meal'] as $meal)
                                <tr>
                                    <td><strong style="color:#d97706;">🍽️ Meal</strong></td>
                                    <td>
                                        {{ $meal['description'] ?? '' }}
                                        <span style="font-size:11px;color:var(--gray-400);display:block;margin-top:2px;">{{ ucfirst($meal['direction'] ?? '') }} · Segment {{ ($meal['segment'] ?? 0) + 1 }}</span>
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

            {{-- ── UPDATE: Enhanced notices ── --}}
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div class="notice purple">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <span>
                        <strong>Repayment Reminders:</strong> You will receive a reminder email 3 days before each instalment due date. 
                        Missing payments may result in booking cancellation and affect your credit record.
                    </span>
                </div>
                @if(!$isTicketed)
                <div class="notice" style="background:var(--blue-lt);border:1px solid var(--blue-md);color:var(--blue);">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    <span>
                        <strong>E-Ticket Status:</strong> Your e-ticket is being issued and will arrive within 15–30 minutes. 
                        Check your email regularly and look in the spam/promotions folder if needed.
                    </span>
                </div>
                @endif
            </div>

            {{-- Actions --}}
            <div style="display:flex;gap:12px;flex-wrap:wrap;" class="btn-row">
                <a href="{{ route('home') }}" class="btn-primary" style="background:linear-gradient(135deg,var(--indigo),var(--purple));">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    Back to Home
                </a>
                <a href="#" onclick="window.print()" class="btn-ghost">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    Print Plan
                </a>
            </div>
        </div>

        {{-- ── RIGHT RAIL ── --}}
        <aside class="pg-rail">

            {{-- TravelFlex Plan Card --}}
            <div class="pc">
                <div style="padding:14px 18px;background:linear-gradient(135deg,var(--navy),var(--indigo),var(--purple));">
                    <div style="font-size:15px;font-weight:800;color:#fff;">📆 TravelFlex Plan</div>
                    <div style="font-size:11px;color:rgba(255,255,255,.65);margin-top:2px;">{{ $repaymentPlan }} · {{ count($schedule) }} instalments</div>
                </div>
                <div class="pc-body-tight">
                    <div class="fare-row"><span class="fare-lbl">Ticket Cost</span><span class="fare-val">{{ $fmt($total) }}</span></div>
                    <div class="fare-row"><span class="fare-lbl">Down Paid ({{ $downPercent }}%)</span><span class="fare-val" style="color:var(--green);">{{ $fmt($downPayment) }}</span></div>
                    <div class="fare-row"><span class="fare-lbl">Balance</span><span class="fare-val">{{ $fmt($remainingBal) }}</span></div>
                    <div class="fare-row"><span class="fare-lbl">Total Interest</span><span class="fare-val" style="color:var(--amber);">{{ $fmt($totalInterest) }}</span></div>
                </div>
                <div class="fare-total" style="padding:12px 18px;">
                    <span style="font-size:13px;font-weight:800;color:var(--navy);">Grand Total</span>
                    <span style="font-size:18px;font-weight:800;color:var(--navy);font-family:var(--mono);">{{ $fmt($grandTotal) }}</span>
                </div>
            </div>

            {{-- Quick Flight Summary --}}
            <div class="pc">
                <div class="pc-head">
                    <div class="pc-icon" style="background:var(--blue-lt);color:var(--blue);">✈️</div>
                    <div><div class="pc-title">Flight Details</div></div>
                </div>
                <div class="pc-body">
                    <div class="dr"><span class="dr-lbl">Route</span><span class="dr-val">@if($isMulti)@foreach($routeLines as $line)<div>{{ $line['route'] }}</div>@endforeach @else {{ ($firstSeg['from']??'') }} → {{ ($finalDest['to']??'') }} @endif</span></div>
                    @if(!empty($mf['departDateLabel']))<div class="dr"><span class="dr-lbl">Departure</span><span class="dr-val">{{ $mf['departDateLabel'] }}</span></div>@endif
                    @if($isReturn && !empty($mf['returnDateLabel']))<div class="dr"><span class="dr-lbl">Return</span><span class="dr-val">{{ $mf['returnDateLabel'] }}</span></div>@endif
                    <div class="dr"><span class="dr-lbl">Trip Type</span><span class="dr-val">{{ $tripLabel }}</span></div>
                    <div class="dr"><span class="dr-lbl">Airline</span><span class="dr-val">{{ $mf['airline'] ?? '—' }}</span></div>
                    <div class="dr"><span class="dr-lbl">Cabin</span><span class="dr-val">{{ $mf['cabin'] ?? 'Economy' }}</span></div>
                    @if($uniqueId)<div class="dr"><span class="dr-lbl">Booking Ref</span><span class="dr-val mono">{{ $bookingRef }}</span></div>@endif
                    <div class="dr"><span class="dr-lbl">Ticket Status</span><span class="dr-val" style="color:{{ $isTicketed ? 'var(--green)' : 'var(--amber)' }};font-weight:700;">{{ $isTicketed ? '✓ Ticketed' : '⏳ Processing' }}</span></div>
                </div>
            </div>

            {{-- Support --}}
            <div style="background:#fff;border:1px solid var(--gray-200);border-radius:var(--radius);padding:16px 18px;box-shadow:var(--shadow-sm);">
                <div style="font-size:13px;font-weight:800;color:var(--gray-900);margin-bottom:10px;">Need Help?</div>
                <div style="font-size:12.5px;color:var(--gray-500);line-height:1.65;">
                    For TravelFlex queries or booking support:<br>
                    📧 <a href="mailto:support@travelwheel.com" style="color:var(--blue);font-weight:600;">support@travelwheel.com</a><br>
                    📞 <strong>+234 800 000 0000</strong><br>
                    Quote ref: <strong style="font-family:var(--mono);color:var(--navy);">{{ $uniqueId }}</strong>
                </div>
            </div>

        </aside>
    </div>
</div>
<script src="//unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endcomponent
