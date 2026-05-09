{{-- resources/views/emails/booking-pending.blade.php --}}
{{-- Email-safe: tables + inline styles only --}}
@php
    $passengers = \App\Support\FlightDisplay::passengers($booking->passengers_snapshot ?? []);
    $cabinLabel = \App\Support\FlightDisplay::cabin($booking->flight_snapshot ?? [], $booking);
    $firstPax  = collect($passengers)->first();
    $firstName = $firstPax['first_name'] ?? 'Traveller';

    $currency  = $booking->currency ?? 'NGN';
    $sym       = match($currency) { 'NGN' => '₦', 'USD' => '$', 'GBP' => '£', 'EUR' => '€', default => $currency . ' ' };
    $price     = $sym . number_format((float)($booking->total_price ?? 0), 2);

    // Ticketing deadline — compute inline so blade doesn't depend on model methods
    $tktLimit    = $booking->tkt_time_limit;
    $tktFmt      = '';
    $tktHours    = 0;
    if ($tktLimit) {
        try {
            $td       = \Carbon\Carbon::parse($tktLimit);
            $tktFmt   = $td->format('D, d M Y \a\t H:i T');
            $tktHours = max(0, (int) now()->diffInHours($td, false));
        } catch (\Throwable) {}
    }

    // ── Extra Services (from DB snapshot) ──────────────────────────────────
    $extraServices = $booking->extra_services_snapshot ?? [];
    $baggageItems  = $extraServices['baggage'] ?? [];
    $mealItems     = $extraServices['meal'] ?? [];
    $extrasTotal   = $extraServices['total_amount'] ?? 0;
    $extrasCurrency = $extraServices['currency'] ?? 'USD';
    $extrasSym = match($extrasCurrency) { 'NGN' => '₦', 'USD' => '$', 'GBP' => '£', 'EUR' => '€', default => $extrasCurrency . ' ' };

    $resumePaymentUrl = $resumePaymentUrl ?? null;
    $paymentMethod = $paymentMethod ?? ($method ?? 'bank_transfer');
    $isHoldNotice = $isHoldNotice ?? false;
    $isBankTransferNotice = $isBankTransferNotice ?? false;
    if (! $isHoldNotice && ! $isBankTransferNotice) {
        $isHoldNotice = $paymentMethod === 'hold';
        $isBankTransferNotice = $paymentMethod === 'bank_transfer';
    }
    $headline = $isHoldNotice ? 'Your Booking is On Hold' : 'Your Payment is Being Verified';
    $subhead = $isHoldNotice
        ? 'Your seat is reserved while you complete payment'
        : 'Payment received - our team is reviewing your transfer';
    $intro = $isHoldNotice
        ? "Thank you for choosing TravelWheel. Your seat has been booked on hold with the airline, and your reservation is waiting for payment. Use the secure link below to continue payment before the hold deadline."
        : "Thank you for choosing TravelWheel. We've received your payment notification and your booking is currently on hold with the airline while our team verifies your transfer.";
    $statusRows = $isHoldNotice
        ? [
            ['done', '1', 'Seat Held', 'Your booking has been placed on hold with the airline.'],
            ['current', '2', 'Payment Pending', 'Complete payment online or by bank transfer before the deadline below.'],
            ['pending', '3', 'E-Ticket Issued', 'Your ticket will be sent to ' . $booking->contact_email . ' once payment is confirmed.'],
        ]
        : [
            ['done', '1', 'Booking Created', 'Your seats are reserved. Ref: ' . $booking->booking_ref],
            ['done', '2', 'Payment Notified', "You've confirmed that payment was made."],
            ['current', '3', 'Verification in Progress', 'Our team is checking your payment. Expected turnaround: 2-4 business hours.'],
            ['pending', '4', 'E-Ticket Issued', 'Your ticket will be emailed to ' . $booking->contact_email . ' immediately after verification.'],
        ];

    // Extract flight snapshot for itinerary display
    $flightSnapshot = $booking->flight_snapshot ?? [];
    $segments = $flightSnapshot['segments'] ?? [];
    $returnSegments = $flightSnapshot['returnSegments'] ?? [];
    $multiLegs = $flightSnapshot['multiLegs'] ?? [];
    $tripType = strtolower($booking->trip_type ?? 'oneway');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking On Hold – {{ $booking->booking_ref }}</title>
<style>
    body{margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Arial,sans-serif;font-size:14px;color:#0f172a}
    .wrap{max-width:600px;margin:32px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.1)}
    .header{background:linear-gradient(135deg,#0a1940,#1e3a8a);padding:28px 32px;text-align:center;color:#fff}
    .header-icon{font-size:42px;margin-bottom:8px}
    .header-title{font-size:20px;font-weight:800;margin-bottom:4px}
    .header-sub{font-size:13px;opacity:.85}
    .body{padding:28px 32px}
    .ref-box{background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:14px 18px;margin-bottom:22px;text-align:center}
    .ref-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#92400e;margin-bottom:4px}
    .ref-value{font-size:22px;font-weight:800;color:#78350f;font-family:'Courier New',monospace;letter-spacing:.05em}
    .section-title{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#94a3b8;margin-bottom:12px;margin-top:20px}
    table.detail{width:100%;border-collapse:collapse;font-size:13px;margin-bottom:8px}
    table.detail td{padding:8px 0;border-bottom:1px solid #f1f5f9;vertical-align:top}
    table.detail td:first-child{color:#64748b;width:40%}
    table.detail td:last-child{font-weight:700;text-align:right}
    .pax-table{width:100%;border-collapse:collapse;font-size:12.5px;margin-top:4px;border:1px solid #e2e8f0}
    .pax-table th{background:#f8fafc;padding:8px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#94a3b8;border-bottom:1px solid #e2e8f0}
    .pax-table td{padding:10px 12px;border-bottom:1px solid #f1f5f9;color:#334155}
    .pax-table tr:last-child td{border-bottom:none}
    .deadline-box{background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:14px 18px;margin:18px 0}
    .deadline-title{font-size:13px;font-weight:800;color:#92400e;margin-bottom:4px}
    .deadline-sub{font-size:12px;color:#78350f;line-height:1.6}
    .btn-wrap{text-align:center;margin:24px 0 8px}
    .btn{display:inline-block;padding:13px 22px;border-radius:10px;background:#0f172a;color:#ffffff!important;text-decoration:none;font-size:13px;font-weight:800}
    .disclaimer{background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:14px 18px;margin:18px 0;font-size:12px;color:#78350f;line-height:1.7}
    .footer{background:#f8fafc;padding:20px 32px;text-align:center;border-top:1px solid #f1f5f9;font-size:12px;color:#94a3b8}
    .footer a{color:#1d4ed8;text-decoration:none}
    /* Itinerary Styles */
    .itinerary-container{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px;margin:16px 0}
    .itinerary-leg{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:14px;margin-bottom:12px}
    .itinerary-leg-header{font-size:12px;font-weight:700;color:#0f172a;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid #e2e8f0}
    .flight-segment{margin-bottom:14px}
    .flight-segment:last-child{margin-bottom:0}
    .segment-row{display:table;width:100%;margin-bottom:10px}
    .segment-time{display:table-cell;width:80px;font-size:16px;font-weight:700;color:#0a1940;vertical-align:middle}
    .segment-route{display:table-cell;padding:0 12px;vertical-align:middle;border-left:2px solid #e2e8f0;border-right:2px solid #e2e8f0}
    .segment-airline{display:table-cell;width:120px;text-align:right;font-size:12px;color:#64748b;vertical-align:middle}
    .airport-code{font-size:13px;font-weight:700;color:#0f172a}
    .airport-name{font-size:11px;color:#64748b}
    .flight-info{font-size:11px;color:#64748b;margin-top:6px}
    .flight-info-item{display:inline-block;margin-right:12px}
    .duration-badge{background:#e0e7ff;color:#3730a3;padding:4px 8px;border-radius:4px;font-size:11px;font-weight:700;display:inline-block;margin-top:6px}
    .layover-badge{background:#fef3c7;color:#92400e;padding:4px 8px;border-radius:4px;font-size:11px;font-weight:700;display:inline-block;margin-top:6px}
    .leg-summary{background:#f0f9ff;border-left:3px solid #0284c7;padding:10px 12px;margin-top:12px;font-size:12px;color:#0c4a6e}
    .leg-summary-row{margin-bottom:4px}
    .leg-summary-row:last-child{margin-bottom:0}
</style>
</head>
<body>
<div class="wrap">

    <div class="header">
        <div class="header-icon">📬</div>
        <div class="header-title">{{ $headline }}</div>
        <div class="header-sub">{{ $subhead }}</div>
    </div>

    <div class="body">
        <p style="font-size:14px;line-height:1.7;margin-bottom:20px;color:#334155">
            Hi <strong>{{ $firstName }}</strong>,<br><br>
            {{ $intro }}
        </p>

        <div class="ref-box">
            <div class="ref-label">Booking Reference</div>
            <div class="ref-value">{{ $booking->booking_ref }}</div>
        </div>

        {{-- ITINERARY SECTION --}}
        <div class="section-title">Flight Itinerary</div>
        <div class="itinerary-container">
            @if($tripType === 'oneway' || $tripType === 'return')
                {{-- OUTBOUND LEG --}}
                @if(!empty($segments))
                <div class="itinerary-leg">
                    <div class="itinerary-leg-header">
                        ✈️ 
                        @if($segments[0])
                            {{ $segments[0]['from'] ?? 'DEP' }} → {{ $segments[count($segments)-1]['to'] ?? 'ARR' }}
                        @endif
                        @if($flightSnapshot['departDateLabel'])
                            | {{ $flightSnapshot['departDateLabel'] }}
                        @endif
                    </div>
                    
                    @foreach($segments as $index => $segment)
                    <div class="flight-segment">
                        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse">
                            <tr>
                                <td style="width:70px;font-size:16px;font-weight:700;color:#0a1940;vertical-align:middle;padding-right:8px">
                                    {{ $segment['departTime'] ?? '—' }}
                                </td>
                                <td style="vertical-align:middle;padding:0 12px;border-left:2px solid #e2e8f0;border-right:2px solid #e2e8f0">
                                    <div class="airport-code">{{ $segment['from'] ?? 'N/A' }}</div>
                                    <div class="airport-name" style="font-size:11px;color:#64748b">{{ $segment['fromCity'] ?? '' }}</div>
                                </td>
                                <td style="text-align:center;padding:0 12px;font-size:12px;color:#64748b;width:60px">
                                    @if($segment['duration'])
                                        {{ floor($segment['duration'] / 60) }}h {{ $segment['duration'] % 60 }}m
                                    @else
                                        —
                                    @endif
                                </td>
                                <td style="vertical-align:middle;padding:0 12px;border-left:2px solid #e2e8f0;border-right:2px solid #e2e8f0">
                                    <div class="airport-code">{{ $segment['to'] ?? 'N/A' }}</div>
                                    <div class="airport-name" style="font-size:11px;color:#64748b">{{ $segment['toCity'] ?? '' }}</div>
                                </td>
                                <td style="font-size:16px;font-weight:700;color:#0a1940;vertical-align:middle;padding-left:8px;text-align:right;width:70px">
                                    {{ $segment['arriveTime'] ?? '—' }}
                                </td>
                            </tr>
                        </table>
                        <div style="font-size:12px;color:#64748b;margin-top:6px;padding-left:8px">
                            <span style="font-weight:700;color:#0f172a">{{ $segment['airlineCode'] ?? '' }} {{ $segment['flightNo'] ?? '' }}</span>
                            @if($segment['equipment'])
                                | Aircraft: {{ $segment['equipment'] }}
                            @endif
                            @if($segment['cabin'])
                                | Cabin: {{ $segment['cabin'] }}
                            @endif
                        </div>
                        
                        {{-- Layover info --}}
                        @if($index < count($segments) - 1)
                            @php
                                $currentArriveStr = ($segment['arriveDT'] ?? '');
                                $nextDepartStr = ($segments[$index + 1]['departDT'] ?? '');
                                if ($currentArriveStr && $nextDepartStr) {
                                    try {
                                        $arrivalTime = \Carbon\Carbon::parse($currentArriveStr);
                                        $departureTime = \Carbon\Carbon::parse($nextDepartStr);
                                        $layoverMins = (int)$arrivalTime->diffInMinutes($departureTime);
                                        $layoverHours = floor($layoverMins / 60);
                                        $layoverMins = $layoverMins % 60;
                                    } catch (\Throwable) {
                                        $layoverMins = 0;
                                        $layoverHours = 0;
                                    }
                                }
                            @endphp
                            @if(($layoverHours ?? 0) > 0)
                            <div style="background:#fef3c7;border-left:3px solid #d97706;padding:8px 10px;margin-top:10px;font-size:11px;color:#92400e;font-weight:700">
                                ⏱️ Layover: {{ $layoverHours }}h {{ $layoverMins }}m in {{ $segment['arriveCity'] ?? $segment['arriveAirport'] ?? 'N/A' }}
                            </div>
                            @endif
                        @endif
                    </div>
                    @endforeach

                    <div style="background:#f0f9ff;border-left:3px solid #0284c7;padding:10px 12px;margin-top:12px;font-size:12px;color:#0c4a6e">
                        <div style="margin-bottom:4px"><strong>Total Duration:</strong> {{ $flightSnapshot['durationLabel'] ?? '—' }}</div>
                        <div><strong>Stops:</strong> {{ $flightSnapshot['stops'] ?? 0 }}</div>
                    </div>
                </div>
                @endif

                {{-- RETURN LEG --}}
                @if($tripType === 'return' && !empty($returnSegments))
                <div class="itinerary-leg">
                    <div class="itinerary-leg-header">
                        ✈️ RETURN
                        @if($returnSegments[0])
                            {{ $returnSegments[0]['from'] ?? 'DEP' }} → {{ $returnSegments[count($returnSegments)-1]['to'] ?? 'ARR' }}
                        @endif
                        @if($flightSnapshot['returnDateLabel'])
                            | {{ $flightSnapshot['returnDateLabel'] }}
                        @endif
                    </div>
                    
                    @foreach($returnSegments as $index => $segment)
                    <div class="flight-segment">
                        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse">
                            <tr>
                                <td style="width:70px;font-size:16px;font-weight:700;color:#0a1940;vertical-align:middle;padding-right:8px">
                                    {{ $segment['departTime'] ?? '—' }}
                                </td>
                                <td style="vertical-align:middle;padding:0 12px;border-left:2px solid #e2e8f0;border-right:2px solid #e2e8f0">
                                    <div class="airport-code">{{ $segment['from'] ?? 'N/A' }}</div>
                                    <div class="airport-name" style="font-size:11px;color:#64748b">{{ $segment['fromCity'] ?? '' }}</div>
                                </td>
                                <td style="text-align:center;padding:0 12px;font-size:12px;color:#64748b;width:60px">
                                    @if($segment['duration'])
                                        {{ floor($segment['duration'] / 60) }}h {{ $segment['duration'] % 60 }}m
                                    @else
                                        —
                                    @endif
                                </td>
                                <td style="vertical-align:middle;padding:0 12px;border-left:2px solid #e2e8f0;border-right:2px solid #e2e8f0">
                                    <div class="airport-code">{{ $segment['to'] ?? 'N/A' }}</div>
                                    <div class="airport-name" style="font-size:11px;color:#64748b">{{ $segment['toCity'] ?? '' }}</div>
                                </td>
                                <td style="font-size:16px;font-weight:700;color:#0a1940;vertical-align:middle;padding-left:8px;text-align:right;width:70px">
                                    {{ $segment['arriveTime'] ?? '—' }}
                                </td>
                            </tr>
                        </table>
                        <div style="font-size:12px;color:#64748b;margin-top:6px;padding-left:8px">
                            <span style="font-weight:700;color:#0f172a">{{ $segment['airlineCode'] ?? '' }} {{ $segment['flightNo'] ?? '' }}</span>
                            @if($segment['equipment'])
                                | Aircraft: {{ $segment['equipment'] }}
                            @endif
                            @if($segment['cabin'])
                                | Cabin: {{ $segment['cabin'] }}
                            @endif
                        </div>
                        
                        {{-- Layover info --}}
                        @if($index < count($returnSegments) - 1)
                            @php
                                $currentArriveStr = ($segment['arriveDT'] ?? '');
                                $nextDepartStr = ($returnSegments[$index + 1]['departDT'] ?? '');
                                if ($currentArriveStr && $nextDepartStr) {
                                    try {
                                        $arrivalTime = \Carbon\Carbon::parse($currentArriveStr);
                                        $departureTime = \Carbon\Carbon::parse($nextDepartStr);
                                        $layoverMins = (int)$arrivalTime->diffInMinutes($departureTime);
                                        $layoverHours = floor($layoverMins / 60);
                                        $layoverMins = $layoverMins % 60;
                                    } catch (\Throwable) {
                                        $layoverMins = 0;
                                        $layoverHours = 0;
                                    }
                                }
                            @endphp
                            @if(($layoverHours ?? 0) > 0)
                            <div style="background:#fef3c7;border-left:3px solid #d97706;padding:8px 10px;margin-top:10px;font-size:11px;color:#92400e;font-weight:700">
                                ⏱️ Layover: {{ $layoverHours }}h {{ $layoverMins }}m in {{ $segment['arriveCity'] ?? $segment['arriveAirport'] ?? 'N/A' }}
                            </div>
                            @endif
                        @endif
                    </div>
                    @endforeach

                    <div style="background:#f0f9ff;border-left:3px solid #0284c7;padding:10px 12px;margin-top:12px;font-size:12px;color:#0c4a6e">
                        <div style="margin-bottom:4px"><strong>Total Duration:</strong> {{ $flightSnapshot['returnDurationLabel'] ?? '—' }}</div>
                        <div><strong>Stops:</strong> {{ $flightSnapshot['returnStops'] ?? 0 }}</div>
                    </div>
                </div>
                @endif

            @elseif($tripType === 'multi' && !empty($multiLegs))
                {{-- MULTI-CITY LEGS --}}
                @foreach($multiLegs as $legIndex => $leg)
                <div class="itinerary-leg">
                    <div class="itinerary-leg-header">
                        ✈️ LEG {{ $legIndex + 1 }}
                        @php
                            $legSegments = $leg['segments'] ?? [];
                            if ($legSegments) {
                                echo $legSegments[0]['from'] ?? 'DEP';
                                echo ' → ';
                                echo $legSegments[count($legSegments)-1]['to'] ?? 'ARR';
                            }
                        @endphp
                        @if($leg['departDateLabel'])
                            | {{ $leg['departDateLabel'] }}
                        @endif
                    </div>
                    
                    @php $legSegments = $leg['segments'] ?? []; @endphp
                    @foreach($legSegments as $index => $segment)
                    <div class="flight-segment">
                        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse">
                            <tr>
                                <td style="width:70px;font-size:16px;font-weight:700;color:#0a1940;vertical-align:middle;padding-right:8px">
                                    {{ $segment['departTime'] ?? '—' }}
                                </td>
                                <td style="vertical-align:middle;padding:0 12px;border-left:2px solid #e2e8f0;border-right:2px solid #e2e8f0">
                                    <div class="airport-code">{{ $segment['from'] ?? 'N/A' }}</div>
                                    <div class="airport-name" style="font-size:11px;color:#64748b">{{ $segment['fromCity'] ?? '' }}</div>
                                </td>
                                <td style="text-align:center;padding:0 12px;font-size:12px;color:#64748b;width:60px">
                                    @if($segment['duration'])
                                        {{ floor($segment['duration'] / 60) }}h {{ $segment['duration'] % 60 }}m
                                    @else
                                        —
                                    @endif
                                </td>
                                <td style="vertical-align:middle;padding:0 12px;border-left:2px solid #e2e8f0;border-right:2px solid #e2e8f0">
                                    <div class="airport-code">{{ $segment['to'] ?? 'N/A' }}</div>
                                    <div class="airport-name" style="font-size:11px;color:#64748b">{{ $segment['toCity'] ?? '' }}</div>
                                </td>
                                <td style="font-size:16px;font-weight:700;color:#0a1940;vertical-align:middle;padding-left:8px;text-align:right;width:70px">
                                    {{ $segment['arriveTime'] ?? '—' }}
                                </td>
                            </tr>
                        </table>
                        <div style="font-size:12px;color:#64748b;margin-top:6px;padding-left:8px">
                            <span style="font-weight:700;color:#0f172a">{{ $segment['airlineCode'] ?? '' }} {{ $segment['flightNo'] ?? '' }}</span>
                            @if($segment['equipment'])
                                | Aircraft: {{ $segment['equipment'] }}
                            @endif
                            @if($segment['cabin'])
                                | Cabin: {{ $segment['cabin'] }}
                            @endif
                        </div>
                        
                        {{-- Layover info --}}
                        @if($index < count($legSegments) - 1)
                            @php
                                $currentArriveStr = ($segment['arriveDT'] ?? '');
                                $nextDepartStr = ($legSegments[$index + 1]['departDT'] ?? '');
                                if ($currentArriveStr && $nextDepartStr) {
                                    try {
                                        $arrivalTime = \Carbon\Carbon::parse($currentArriveStr);
                                        $departureTime = \Carbon\Carbon::parse($nextDepartStr);
                                        $layoverMins = (int)$arrivalTime->diffInMinutes($departureTime);
                                        $layoverHours = floor($layoverMins / 60);
                                        $layoverMins = $layoverMins % 60;
                                    } catch (\Throwable) {
                                        $layoverMins = 0;
                                        $layoverHours = 0;
                                    }
                                }
                            @endphp
                            @if(($layoverHours ?? 0) > 0)
                            <div style="background:#fef3c7;border-left:3px solid #d97706;padding:8px 10px;margin-top:10px;font-size:11px;color:#92400e;font-weight:700">
                                ⏱️ Layover: {{ $layoverHours }}h {{ $layoverMins }}m in {{ $segment['arriveCity'] ?? $segment['arriveAirport'] ?? 'N/A' }}
                            </div>
                            @endif
                        @endif
                    </div>
                    @endforeach

                    <div style="background:#f0f9ff;border-left:3px solid #0284c7;padding:10px 12px;margin-top:12px;font-size:12px;color:#0c4a6e">
                        <div style="margin-bottom:4px"><strong>Duration:</strong> {{ $leg['durationLabel'] ?? '—' }}</div>
                        <div><strong>Stops:</strong> {{ $leg['stops'] ?? 0 }}</div>
                    </div>
                </div>
                @endforeach
            @else
                <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:6px;padding:12px;font-size:12px;color:#7f1d1d">
                    No itinerary details available
                </div>
            @endif
        </div>

        @if(!empty($passengers))
        <div class="section-title">Passengers</div>
        <table class="pax-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>DOB</th>
                    <th>Passport</th>
                </tr>
            </thead>
            <tbody>
                @foreach($passengers as $i => $pax)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td><strong>{{ $pax['title'] ?? '' }} {{ strtoupper($pax['first_name'] ?? '') }} {{ strtoupper($pax['last_name'] ?? '') }}</strong></td>
                    <td>{{ match($pax['type'] ?? 'ADT') { 'ADT' => 'Adult', 'CHD' => 'Child', 'INF' => 'Infant', default => 'Pax' } }}</td>
                    <td>{{ !empty($pax['dob']) ? \Carbon\Carbon::parse($pax['dob'])->format('d M Y') : '-' }}</td>
                    <td>{{ $pax['passport_no'] ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <div class="section-title">Booking Summary</div>
        <table class="detail">
            <tr><td>Airline</td><td>{{ $booking->airline }}</td></tr>
            <tr><td>Cabin</td><td>{{ $cabinLabel }}</td></tr>
            <tr><td>Fare Type</td><td>{{ $booking->fare_type }}</td></tr>
            @if(!empty($baggageItems) || !empty($mealItems))
                <tr><td colspan="2" style="padding:8px 0;border-bottom:1px dashed #cbd5e1"><strong>Extra Services</strong></td></tr>
                @foreach($baggageItems as $bag)
                <tr><td>🧳 {{ $bag['description'] }}</td><td style="color:#059669">{{ $extrasSym }}{{ number_format($bag['line_total'], 2) }}</td></tr>
                @endforeach
                @foreach($mealItems as $meal)
                <tr><td>🍽️ {{ $meal['description'] }}</td><td style="color:#d97706">{{ $extrasSym }}{{ number_format($meal['unit_price'], 2) }}</td></tr>
                @endforeach
            @endif
            <tr><td>Total Amount</td><td style="color:#0a1940;font-size:15px"><strong>{{ $sym }}{{ number_format((float)($booking->total_price ?? 0) + $extrasTotal, 2) }}</strong></td></tr>
            <tr><td>Payment Method</td><td>{{ $isBankTransferNotice ? 'Bank Transfer' : 'Online or Bank Transfer' }}</td></tr>
        </table>

        @if($tktFmt)
        <div class="deadline-box">
            <div class="deadline-title">Ticketing Deadline</div>
            <div class="deadline-sub">
                Your booking hold expires on <strong>{{ $tktFmt }}</strong>
                ({{ $tktHours }} hour{{ $tktHours === 1 ? '' : 's' }} remaining).
                Please ensure payment is completed before this time.
            </div>
        </div>
        @endif

        @if($resumePaymentUrl)
        <div class="btn-wrap">
            <a href="{{ $resumePaymentUrl }}" class="btn">Continue Payment</a>
        </div>
        <p style="font-size:12px;color:#64748b;line-height:1.7;text-align:center;margin-bottom:0">
            You can use this link later to return directly to your payment options.
        </p>
        @endif

        <div class="disclaimer">
            Your reservation is held subject to airline rules and availability. It can be canceled at the discretion of the airline on or before the due date.
        </div>

        <div class="section-title">What Happens Next</div>

        {{-- Steps rendered as a table for email-client compatibility --}}
        <table width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0">
            @foreach($statusRows as [$state, $icon, $title, $sub])
            <tr style="margin-bottom:14px">
                <td style="width:36px;vertical-align:top;padding:0 12px 14px 0">
                    <div style="
                        width:28px;height:28px;border-radius:50%;
                        background:{{ $state === 'done' ? '#059669' : ($state === 'current' ? '#d97706' : '#94a3b8') }};
                        color:#fff;font-size:12px;font-weight:800;
                        text-align:center;line-height:28px;
                    ">{{ $icon }}</div>
                </td>
                <td style="padding-bottom:14px;vertical-align:top">
                    <div style="font-size:13px;font-weight:700;color:#0f172a;margin-bottom:2px">{{ $title }}</div>
                    <div style="font-size:12px;color:#64748b;line-height:1.5">{{ $sub }}</div>
                </td>
            </tr>
            @endforeach
        </table>

        <p style="font-size:13px;color:#64748b;line-height:1.7;margin-top:16px">
            <strong>Need help?</strong> Our team is available Mon–Fri 8am–6pm WAT.<br>
            Email: <a href="mailto:support@travelwheel.ng">support@travelwheel.ng</a>
            | Phone: +234 800 000 0000<br>
            Always quote your booking reference: <strong>{{ $booking->booking_ref }}</strong>
        </p>
    </div>

    <div class="footer">
        &copy; {{ date('Y') }} TravelWheel. All rights reserved.<br>
        <a href="#">Privacy Policy</a> &middot; <a href="#">Terms of Service</a>
    </div>

</div>
</body>
</html>
