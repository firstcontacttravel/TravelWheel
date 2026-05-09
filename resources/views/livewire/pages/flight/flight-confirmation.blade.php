{{-- resources/views/livewire/pages/flight/flight-confirmation.blade.php --}}
@component('layouts.app', ['title' => 'Booking Confirmed'])

    @php
        // ── Core session data ──────────────────────────────────────────────────────
        $dbBooking      = $dbBooking ?? null;
        $bookingFlight  = session('bookingFlight', []);
        $mf             = $flight ?? ($bookingFlight['flight'] ?? $bookingFlight);
        if (empty($mf) && $dbBooking) {
            $mf = $dbBooking->flight_snapshot ?? [];
        }

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

        $breakdown     = $bookingFlight['fareBreakdown'] ?? $mf['fareBreakdown'] ?? [];
        $contact       = $contact ?? session('bookingContact', []);
        if (empty($contact) && $dbBooking) {
            $contact = ['email' => $dbBooking->contact_email, 'phone' => $dbBooking->contact_phone];
        }
        $passengers    = $passengers ?? session('bookingPassengers', []);
        if (empty($passengers) && $dbBooking) {
            $passengers = $dbBooking->passengers_snapshot ?? [];
        }
        $passengers = \App\Support\FlightDisplay::passengers($passengers);
        $cabinLabel = \App\Support\FlightDisplay::cabin($mf, $dbBooking);
        $total         = (float)($mf['price'] ?? 0);
        $uniqueId      = $uniqueId ?? session('bookingUniqueId', $dbBooking?->unique_id ?? '');    // API e-ticket / hold ref
        $bookingRef    = $bookingRef ?? session('bookingRef', $dbBooking?->booking_ref ?? ''); // OUR ref
        $paymentMethod = $paymentMethod ?? session('paymentMethod', $dbBooking?->payment_method ?? 'gateway');
        $tripDetails   = $tripDetails ?? [];   // passed from controller after _callTripDetailsApi()

        // ── Extra Services (from DB snapshot) ──────────────────────────────────
        $extraServices = $dbBooking?->extra_services_snapshot ?? [];
        $baggageItems  = $extraServices['baggage'] ?? [];
        $mealItems     = $extraServices['meal'] ?? [];
        $extrasTotal   = $extraServices['total_amount'] ?? 0;
        $extrasCurrency = $extraServices['currency'] ?? 'USD';

        // Live data from trip_details API
        $resItems      = collect(data_get($tripDetails, 'ItineraryInfo.ReservationItems', []))->map(fn($r) => $r['ReservationItem'] ?? $r);
        $customerInfos = collect(data_get($tripDetails, 'ItineraryInfo.CustomerInfos', []))->map(fn($c) => $c['CustomerInfo'] ?? $c);
        $bookingStatus = $tripDetails['BookingStatus'] ?? 'Confirmed';
        $ticketStatus  = $tripDetails['TicketStatus']  ?? '';

        // Build PNR map: flightNo → PNR
        $pnrMap = [];
        foreach ($resItems as $ri) {
            $key = ($ri['MarketingAirlineCode'] ?? '') . ($ri['FlightNumber'] ?? '');
            $pnrMap[$key] = $ri['AirlinePNR'] ?? '';
        }
        // e-Ticket map: ItemRPH → eTicketNumber
        $eticketMap = [];
        foreach ($customerInfos as $c) {
            if (!empty($c['eTicketNumber'])) $eticketMap[$c['ItemRPH']] = $c['eTicketNumber'];
        }
        $hasTripData = !empty($tripDetails);

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
    @endphp

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
   

    /* ── Hero ── */
    .hero { border-radius: 18px; padding: 32px 28px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 22px; position: relative; overflow: hidden; }
    .hero.confirmed { background: linear-gradient(135deg, #064e3b 0%, #065f46 40%, var(--teal) 100%); }
    .hero::before   { content:''; position:absolute; top:-60px; right:-60px; width:280px; height:280px; background:radial-gradient(circle,rgba(255,255,255,.1) 0%,transparent 70%); pointer-events:none; }
    .hero-icon  { width: 72px; height: 72px; border-radius: 50%; background: rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; font-size: 34px; flex-shrink: 0; position: relative; z-index: 2; }
    .hero-title { font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 6px; position: relative; z-index: 2; }
    .hero-sub   { font-size: 13.5px; color: rgba(255,255,255,.85); line-height: 1.65; max-width: 500px; position: relative; z-index: 2; }
    .hero-ref   { display: inline-flex; align-items: center; gap: 8px; margin-top: 14px; padding: 8px 18px; background: rgba(255,255,255,.15); border-radius: 10px; font-size: 14px; font-weight: 800; color: #fff; font-family: var(--mono); letter-spacing: .04em; position: relative; z-index: 2; }
    .hero-ref-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; opacity: .7; display: block; margin-bottom: 2px; }

    /* ── Booking ref strip ── */
    .ref-strip { background: var(--green-lt); border: 1.5px solid var(--green-md); border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
    .ref-strip-id { font-size: 22px; font-weight: 800; color: var(--navy); font-family: var(--mono); letter-spacing: .05em; }
    .ref-strip-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: var(--green); margin-bottom: 3px; }

    /* ── Itinerary visual ── */
    .itin-visual { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 16px 20px; background: var(--gray-50); border-bottom: 1px solid var(--gray-100); flex-wrap: wrap; }
    .itin-airport { text-align: center; }
    .itin-iata { font-size: 28px; font-weight: 800; color: var(--navy); font-family: var(--mono); }
    .itin-city { font-size: 11px; color: var(--gray-400); margin-top: 2px; }
    .itin-arrow { flex: 1; display: flex; align-items: center; gap: 6px; justify-content: center; }
    .itin-line { flex: 1; height: 1px; background: var(--gray-300); max-width: 80px; }
    .itin-plane-icon { color: var(--blue); }

    /* ── E-ticket card ── */
    .eticket-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 10px; }
    .eticket-item { background: var(--indigo-lt); border: 1px solid #c7d2fe; border-radius: 10px; padding: 12px 14px; }
    .eticket-pax  { font-size: 12px; font-weight: 700; color: var(--indigo); margin-bottom: 4px; }
    .eticket-num  { font-size: 14px; font-weight: 800; color: var(--navy); font-family: var(--mono); }

    /* ── Print override ── */
    @media print { .hero-icon { display: none; } .hero { padding: 16px; } }
    
</style>
@include('livewire.pages.flight.partials._shared_styles');
<div class="pg-wrap" x-data="{}">

    {{-- ── Hero ── --}}
    {{-- ── UPDATE: Status indicator section ── --}}
    @php
        $ticketStatus = $tripDetails['TicketStatus'] ?? 'UNKNOWN';
        $bookingStatus = $tripDetails['BookingStatus'] ?? 'CONFIRMED';
        $isTicketed = strtoupper($ticketStatus) === 'TICKETED';
        $isConfirmedOnly = strtoupper($bookingStatus) === 'CONFIRMED' && !$isTicketed;
    @endphp

    {{-- ── Hero (update status message) ── --}}
    <div class="hero confirmed">
        <div class="hero-icon">{{ $isTicketed ? '✅' : '⏳' }}</div>
        <div style="position:relative;z-index:2;flex:1;">
            <div class="hero-title">
                {{ $isTicketed ? 'Booking Confirmed & Ticketed!' : 'Booking Confirmed — Ticketing in Progress' }}
            </div>
            <div class="hero-sub">
                @if($isTicketed)
                    Your flight has been booked and your e-ticket issued.
                    A confirmation email has been sent to <strong>{{ $contact['email'] ?? '' }}</strong>.
                    Present your e-ticket at the airport check-in counter.
                @else
                    Your booking is confirmed! Our system is processing your e-ticket.
                    You will receive your e-ticket via email at <strong>{{ $contact['email'] ?? '' }}</strong>
                    within the next 15-30 minutes. In the meantime, your seat is reserved.
                @endif
            </div>
            @if($bookingRef)
            <div class="hero-ref">
                <div>
                    <span class="hero-ref-label">Booking Reference</span>
                    {{ $bookingRef }}
                </div>
                <span style="font-size:11px;padding:3px 10px;background:rgba(255,255,255,.2);border-radius:999px;">
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

        {{-- ══ MAIN COLUMN ══ --}}
        <div class="pg-main">

            {{-- ── Booking Reference Strip ── --}}
            @if($bookingRef)
            <div class="ref-strip">
                <div>
                    <div class="ref-strip-label">Your Booking Reference</div>
                    <div class="ref-strip-id">{{ $bookingRef }}</div>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    <span class="status-badge" style="background:{{ $isTicketed ? 'var(--green-lt)' : 'var(--blue-lt)' }};color:{{ $isTicketed ? 'var(--green)' : 'var(--blue)' }};">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round">
                            @if($isTicketed)
                                <polyline points="20 6 9 17 4 12"/>
                            @else
                                <line x1="12" y1="5" x2="12" y2="19"/>
                                <line x1="5" y1="12" x2="19" y2="12"/>
                            @endif
                        </svg>
                        {{ $isTicketed ? 'Ticketed' : 'Confirmed' }}
                    </span>
                    @if(!empty($ticketStatus))
                    <span style="font-size:11.5px;color:var(--teal);font-weight:700;background:var(--teal-lt);padding:4px 10px;border-radius:999px;">
                        {{ $isTicketed ? '🎫 Ticketed' : '⏳ Processing' }}
                    </span>
                    @endif
                </div>
            </div>
            @endif

            {{-- ── Flight Itinerary ── --}}
            <div class="pc">
                <div class="pc-head">
                    <div class="pc-icon" style="background:var(--blue-lt);color:var(--blue);">✈️</div>
                    <div>
                        <div class="pc-title">Flight Itinerary</div>
                        <div class="pc-sub">{{ $tripLabel }} · {{ $cabinLabel }} · {{ $mf['airline'] ?? '' }}</div>
                    </div>
                </div>

                {{-- Visual route header --}}
                <div class="itin-visual">
                    <div class="itin-airport">
                        <div class="itin-iata">{{ $firstSeg['from'] ?? '' }}</div>
                        <div class="itin-city">{{ $firstSeg['fromCity'] ?? '' }}</div>
                        <div style="font-size:11px;color:var(--gray-500);margin-top:2px;">{{ !empty($mf['departDateLabel']) ? $mf['departDateLabel'] : '' }}</div>
                    </div>
                    <div class="itin-arrow">
                        <div class="itin-line"></div>
                        <svg class="itin-plane-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M17.8 19.2L16 11l3.5-3.5C21 6 21 4 19 4c-2 0-2 0-3.5 1.5L11 9l-8.2-1.8A1 1 0 0 0 2 8.05L3.95 11a1 1 0 0 0 .67.45L11 13l-1 4.5a1 1 0 0 0 .28.95L12 20a1 1 0 0 0 1.6-.35l1.54-3.81A1 1 0 0 1 15.8 15H21a1 1 0 0 0 .92-1.38l-2-5.2"/></svg>
                        <div class="itin-line"></div>
                    </div>
                    <div class="itin-airport">
                        <div class="itin-iata">{{ $finalDest['to'] ?? '' }}</div>
                        <div class="itin-city">{{ $finalDest['toCity'] ?? '' }}</div>
                        @if($isReturn && !empty($mf['returnDateLabel']))<div style="font-size:11px;color:var(--gray-500);margin-top:2px;">{{ $mf['returnDateLabel'] }}</div>@endif
                    </div>
                </div>

                {{-- ── OUTBOUND LEG ── --}}
                @if(!$isMulti)
                @include('livewire.pages.flight.partials._render_leg', [
                    'legSegs'       => $segments,
                    'legLabel'      => 'Outbound',
                    'legBadgeClass' => 'outbound',
                    'legLayovers'   => $mf['layoverDurations'] ?? [],
                    'legStops'      => $mf['stops'] ?? max(0, count($segments)-1),
                    'legDuration'   => $mf['totalTimeLabel'] ?? $mf['durationLabel'] ?? '',
                    'legDate'       => $mf['departDateLabel'] ?? '',
                    'breakdown'     => $breakdown,
                    'equipMap'      => $equipMap,
                    'tripDetails'   => $tripDetails,
                ])
                @endif

                {{-- ── RETURN LEG ── --}}
                @if($isReturn && !empty($retSegs))
                @include('livewire.pages.flight.partials._render_leg', [
                    'legSegs'       => $retSegs,
                    'legLabel'      => 'Return',
                    'legBadgeClass' => 'inbound',
                    'legLayovers'   => $mf['returnLayoverDurations'] ?? [],
                    'legStops'      => $mf['returnStops'] ?? max(0, count($retSegs)-1),
                    'legDuration'   => $mf['returnTotalTimeLabel'] ?? $mf['returnDurationLabel'] ?? '',
                    'legDate'       => $mf['returnDateLabel'] ?? '',
                    'breakdown'     => $breakdown,
                    'equipMap'      => $equipMap,
                    'tripDetails'   => $tripDetails,
                ])
                @endif

                {{-- ── MULTI-CITY EXTRA LEGS ── --}}
                @if($isMulti)
                    @foreach($multiLegs as $li => $leg)
                        @php $legSegs = $leg['segments'] ?? []; @endphp
                        @if(!empty($legSegs))
                        @include('livewire.pages.flight.partials._render_leg', [
                            'legSegs'       => $legSegs,
                            'legLabel'      => 'Leg ' . ($li + 1),
                            'legBadgeClass' => 'multi',
                            'legLayovers'   => $leg['layoverDurations'] ?? [],
                            'legStops'      => $leg['stops'] ?? max(0, count($legSegs)-1),
                            'legDuration'   => $leg['totalTimeLabel'] ?? $leg['durationLabel'] ?? '',
                            'legDate'       => $leg['departDateLabel'] ?? '',
                            'breakdown'     => $breakdown,
                            'equipMap'      => $equipMap,
                            'tripDetails'   => $tripDetails,
                        ])
                        @endif
                    @endforeach
                @endif
            </div>

            {{-- ── UPDATE: Status alert for CONFIRMED (not ticketed) ── --}}
            @if($isConfirmedOnly)
            <div class="notice amber" style="background:var(--amber-lt);border:1px solid var(--amber-md);border-radius:12px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="color:var(--amber);flex-shrink:0;"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
                <div>
                    <div style="font-weight:700;color:var(--amber-dark);margin-bottom:3px;">Ticketing in Progress</div>
                    <div style="font-size:13px;color:var(--amber-dark);line-height:1.6;">
                        Your booking is confirmed and your seat is reserved. Your e-ticket is being processed and will be emailed to <strong>{{ $contact['email'] ?? '' }}</strong> shortly (usually within 15–30 minutes).
                        <br><br>
                        <strong>Check your email (including spam/promotions folder)</strong> for your ticket. If you don't receive it within 1 hour, contact support with your booking reference.
                    </div>
                </div>
            </div>
            @endif

            {{-- ── E-Ticket Numbers (show conditionally) ── --}}
            @php
                $ticketOrderUniqueId = data_get($ticketOrderResult ?? [], 'AirOrderTicketRS.TicketOrderResult.UniqueID', '');
            @endphp
            @if($isTicketed && (!empty($eticketMap) || !empty($ticketOrderUniqueId) || $hasTripData))
            <div class="pc">
                <div class="pc-head">
                    <div class="pc-icon" style="background:var(--indigo-lt);color:var(--indigo);">🎫</div>
                    <div>
                        <div class="pc-title">E-Ticket Numbers</div>
                        <div class="pc-sub">Keep these for check-in — one per passenger</div>
                    </div>
                </div>
                <div class="pc-body">
                    @if(!empty($eticketMap))
                    <div class="eticket-grid">
                        @foreach($customerInfos as $c)
                            @if(!empty($c['eTicketNumber']))
                            <div class="eticket-item">
                                <div class="eticket-pax">{{ $c['PassengerTitle']??'' }} {{ $c['PassengerFirstName']??'' }} {{ $c['PassengerLastName']??'' }}</div>
                                <div class="eticket-num">{{ $c['eTicketNumber'] }}</div>
                                <div style="font-size:10.5px;color:var(--indigo);margin-top:3px;">{{ match($c['PassengerType']??'ADT'){'ADT'=>'Adult','CHD'=>'Child','INF'=>'Infant',default=>'Passenger'} }}</div>
                            </div>
                            @endif
                        @endforeach
                    </div>
                    @elseif(!empty($ticketOrderUniqueId))
                    <div class="eticket-grid">
                        <div class="eticket-item">
                            <div class="eticket-pax">E-Ticket Reference</div>
                            <div class="eticket-num">{{ $ticketOrderUniqueId }}</div>
                            <div style="font-size:10.5px;color:var(--indigo);margin-top:3px;">
                                Your full e-ticket details have been emailed to <strong>{{ $contact['email'] ?? '' }}</strong>
                            </div>
                        </div>
                    </div>
                    @endif
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
                            This typically takes 15–30 minutes. Refresh this page or check your email for updates.
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- ── Passengers ── --}}
            @if(!empty($passengers))
            <div class="pc">
                <div class="pc-head">
                    <div class="pc-icon" style="background:#f0f9ff;color:#0369a1;">👥</div>
                    <div>
                        <div class="pc-title">Passengers ({{ count($passengers) }})</div>
                        <div class="pc-sub">Names as submitted for ticketing</div>
                    </div>
                </div>
                <div class="pc-body" style="padding:0;">
                    <table class="pax-table" style="width:100%;">
                        <thead>
                            <tr><th>#</th><th>Name</th><th>Type</th><th>Date of Birth</th><th>Nationality</th><th>Passport</th></tr>
                        </thead>
                        <tbody>
                            @foreach($passengers as $i => $pax)
                            @php
                                $ptColors = match($pax['type']??'ADT'){
                                    'ADT' => ['#dbeafe','#1d4ed8'],
                                    'CHD' => ['#fef3c7','#d97706'],
                                    'INF' => ['#f0fdf4','#059669'],
                                    default => ['#f1f5f9','#64748b'],
                                };
                            @endphp
                            <tr>
                                <td style="color:var(--gray-400)">{{ $i + 1 }}</td>
                                <td><strong>{{ $pax['title']??'' }} {{ strtoupper($pax['first_name']??'') }} {{ strtoupper($pax['last_name']??'') }}</strong></td>
                                <td><span class="pax-badge" style="background:{{$ptColors[0]}};color:{{$ptColors[1]}}">{{ match($pax['type']??'ADT'){'ADT'=>'Adult','CHD'=>'Child','INF'=>'Infant',default=>'Pax'} }}</span></td>
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

            {{-- ── Important Reminders ── --}}
            <div class="pc">
                <div class="pc-head">
                    <div class="pc-icon" style="background:var(--amber-lt);color:var(--amber);">📋</div>
                    <div>
                        <div class="pc-title">Important Reminders</div>
                    </div>
                </div>
                <div class="pc-body" style="display:flex;flex-direction:column;gap:10px;">
                    @foreach([
                        ['✈️', 'Check-in', 'Arrive at least <strong>2 hours</strong> before domestic flights, <strong>3 hours</strong> before international flights.'],
                        ['🪪', 'Valid ID Required', 'Carry a valid photo ID or passport. Names <strong>must match</strong> exactly as printed on your ticket.'],
                        ['🧳', 'Baggage', 'Check your airline\'s baggage allowance. Excess baggage fees apply at the airport.'],
                        ['📱', 'Online Check-in', 'Most airlines open online check-in 24–48 hours before departure. Check your airline\'s website.'],
                    ] as [$icon, $title, $text])
                    <div style="display:flex;align-items:flex-start;gap:12px;padding:10px 12px;background:var(--gray-50);border-radius:10px;border:1px solid var(--gray-100);">
                        <span style="font-size:20px;flex-shrink:0;">{{ $icon }}</span>
                        <div>
                            <div style="font-size:13px;font-weight:700;color:var(--gray-900);margin-bottom:2px;">{{ $title }}</div>
                            <div style="font-size:12.5px;color:var(--gray-500);line-height:1.5;">{!! $text !!}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- ── Actions ── --}}
            <div style="display:flex;gap:12px;flex-wrap:wrap;" class="btn-row">
                <a href="{{ route('home') }}" class="btn-primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    Back to Home
                </a>
                <a href="#" onclick="window.print()" class="btn-ghost">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    Print Itinerary
                </a>
            </div>
        </div>

        {{-- ══ RIGHT RAIL ══ --}}
        <aside class="pg-rail">

            {{-- Fare Summary --}}
            <div class="pc">
                <div style="padding:14px 18px;background:var(--navy);">
                    <div style="font-size:15px;font-weight:800;color:#fff;">Fare Summary</div>
                    <div style="font-size:11px;color:rgba(255,255,255,.6);margin-top:2px;">{{ $tripLabel }} · {{ count($passengers) }} passenger{{ count($passengers)>1?'s':'' }}</div>
                </div>
                <div class="pc-body-tight">
                    @foreach($breakdown as $fb)
                    @php
                        $ptype = match($fb['passengerType']??'ADT'){'ADT'=>'Adult','CHD'=>'Child','INF'=>'Infant',default=>'Pax'};
                        $qty   = $fb['qty'] ?? 1;
                    @endphp
                    <div class="fare-row">
                        <span class="fare-lbl">{{ $ptype }} × {{ $qty }}</span>
                        <span class="fare-val">{{ $fmt(($fb['totalFare']??0) * $qty) }}</span>
                    </div>
                    @endforeach
                </div>

                {{-- ── Extra Services (if any) ── --}}
                @if(!empty($baggageItems) || !empty($mealItems))
                <div style="padding:12px 16px;border-top:1px solid var(--gray-100);background:var(--gray-50);">
                    <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--gray-400);margin-bottom:8px;">Extra Services</div>
                    @foreach($baggageItems as $bag)
                    <div class="fare-row">
                        <span class="fare-lbl">🧳 {{ $bag['description'] }} ({{ ucfirst($bag['direction']) }})</span>
                        <span class="fare-val" style="color:var(--green);">{{ match($bag['currency']){'NGN'=>'₦','USD'=>'$','GBP'=>'£','EUR'=>'€',default=>$bag['currency'].' '} }}{{ number_format($bag['line_total'], 2) }}</span>
                    </div>
                    @endforeach
                    @foreach($mealItems as $meal)
                    <div class="fare-row">
                        <span class="fare-lbl">🍽️ {{ $meal['description'] }} (Seg {{ $meal['segment'] + 1 }}, {{ ucfirst($meal['direction']) }})</span>
                        <span class="fare-val" style="color:var(--amber);">{{ match($meal['currency']){'NGN'=>'₦','USD'=>'$','GBP'=>'£','EUR'=>'€',default=>$meal['currency'].' '} }}{{ number_format($meal['unit_price'], 2) }}</span>
                    </div>
                    @endforeach
                </div>
                @endif

                <div class="fare-total">
                    <span class="fare-total-lbl">Total Paid</span>
                    <span class="fare-total-val">{{ $fmt($total + $extrasTotal) }}</span>
                </div>
            </div>

            {{-- Contact --}}
            <div class="pc">
                <div class="pc-head">
                    <div class="pc-icon" style="background:var(--blue-lt);color:var(--blue);">📧</div>
                    <div><div class="pc-title">Contact Details</div></div>
                </div>
                <div class="pc-body">
                    <div class="dr"><span class="dr-lbl">Email</span><span class="dr-val" style="font-size:12px">{{ $contact['email'] ?? '—' }}</span></div>
                    <div class="dr"><span class="dr-lbl">Phone</span><span class="dr-val">{{ $contact['phone'] ?? '—' }}</span></div>
                    @if($bookingRef)<div class="dr"><span class="dr-lbl">Booking Ref</span><span class="dr-val mono">{{ $bookingRef }}</span></div>@endif
                    @if($uniqueId)<div class="dr"><span class="dr-lbl">E-Ticket Ref</span><span class="dr-val mono" style="font-size:11px;">{{ $uniqueId }}</span></div>@endif
                    <div class="dr"><span class="dr-lbl">Payment</span><span class="dr-val">{{ ucfirst(str_replace('_',' ',$paymentMethod)) }}</span></div>
                </div>
            </div>

            {{-- Support --}}
            <div style="background:#fff;border:1px solid var(--gray-200);border-radius:var(--radius);padding:16px 18px;box-shadow:var(--shadow-sm);">
                <div style="font-size:13px;font-weight:800;color:var(--gray-900);margin-bottom:10px;">Need Help?</div>
                <div style="font-size:12.5px;color:var(--gray-500);line-height:1.65;">
                    Our support team is available <strong>Mon–Fri 8am–6pm</strong>.<br>
                    📧 <a href="mailto:support@travelwheel.com" style="color:var(--blue);font-weight:600;">support@travelwheel.com</a><br>
                    📞 <strong>+234 800 000 0000</strong><br><br>
                    Always quote your booking reference:<br>
                    <strong style="font-family:var(--mono);color:var(--navy);">{{ $bookingRef ?: $uniqueId }}</strong>
                </div>
            </div>

        </aside>
    </div>
</div>
<script src="//unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endcomponent
