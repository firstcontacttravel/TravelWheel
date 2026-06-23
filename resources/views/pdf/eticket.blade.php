<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>E-Ticket - {{ $bookingRef }}</title>
<style>
    * { box-sizing: border-box; }
    body {
        margin: 0;
        padding: 0;
        background: #ffffff;
        color: #111827;
        font-family: DejaVu Sans, sans-serif;
        font-size: 10px;
        line-height: 1.45;
    }
    .page { padding: 24px 30px; }
    .hero {
        background: #303191;
        color: #ffffff;
        border-radius: 12px;
        padding: 22px 24px;
    }
    .hero table { width: 100%; border-collapse: collapse; }
    .brand { font-size: 22px; font-weight: 800; letter-spacing: -.2px; }
    .brand-sub { margin-top: 3px; color: #dfffea; font-size: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: .9px; }
    .status { display: inline-block; padding: 6px 12px; border-radius: 20px; background: #eafff0; color: #007a2a; font-size: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: .5px; }
    .status.pending { background: #fff7ed; color: #b45309; }
    .hero-title { margin-top: 22px; font-size: 18px; font-weight: 800; }
    .hero-copy { margin-top: 5px; color: #edf4ff; font-size: 9px; max-width: 430px; }
    .meta-grid { width: 100%; margin-top: 14px; border-collapse: separate; border-spacing: 0; border: 1px solid #e6e8ee; border-radius: 10px; overflow: hidden; }
    .meta-grid td { width: 33.33%; padding: 13px 14px; border-right: 1px solid #e6e8ee; background: #fbfcfe; vertical-align: top; }
    .meta-grid td.last { border-right: none; }
    .label { color: #667085; font-size: 7px; font-weight: 800; text-transform: uppercase; letter-spacing: .7px; }
    .value { margin-top: 4px; color: #111827; font-size: 12px; font-weight: 800; }
    .mono { font-family: DejaVu Sans Mono, monospace; }
    .primary { color: #303191; }
    .section-title { margin: 16px 0 8px; color: #667085; font-size: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: .8px; }
    .notice { margin-top: 12px; padding: 10px 12px; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 9px; color: #7c2d12; font-size: 8.5px; }
    .flight-card { margin-bottom: 10px; border: 1px solid #e6e8ee; border-radius: 10px; overflow: hidden; page-break-inside: avoid; }
    .flight-head { width: 100%; border-collapse: collapse; }
    .flight-head td { padding: 10px 12px; background: #fbfcfe; border-bottom: 1px solid #eef0f4; vertical-align: top; }
    .leg-badge { display: inline-block; padding: 3px 8px; border-radius: 20px; background: #f7f7ff; color: #303191; font-size: 7px; font-weight: 800; text-transform: uppercase; letter-spacing: .4px; }
    .leg-badge.return { background: #eafff0; color: #009933; }
    .leg-badge.leg { background: #eef2ff; color: #254277; }
    .flight-no { margin-left: 8px; font-size: 10px; font-weight: 800; color: #111827; }
    .flight-airline { margin-top: 3px; color: #667085; font-size: 8px; }
    .flight-date { text-align: right; color: #303191; font-size: 8px; font-weight: 800; }
    .route-table { width: 100%; border-collapse: collapse; }
    .route-table td { padding: 13px 12px; vertical-align: middle; }
    .route-point { width: 30%; }
    .route-point.right { text-align: right; }
    .time { color: #111827; font-size: 15px; font-weight: 800; }
    .iata { margin-top: 2px; color: #303191; font-size: 22px; font-weight: 800; line-height: 1; }
    .city { margin-top: 3px; color: #667085; font-size: 7.5px; }
    .route-mid { width: 40%; text-align: center; }
    .duration { color: #111827; font-size: 8px; font-weight: 800; }
    .route-line { height: 1px; margin: 7px 10px 5px; background: #a6adbb; }
    .stops { color: #009933; font-size: 7px; font-weight: 800; }
    .flight-meta-row { width: 100%; border-collapse: collapse; background: #fbfcfe; border-top: 1px solid #eef0f4; }
    .flight-meta-row td { width: 25%; padding: 8px 10px; vertical-align: top; }
    .flight-meta-row span { display: block; color: #667085; font-size: 7px; font-weight: 800; text-transform: uppercase; }
    .flight-meta-row strong { display: block; margin-top: 2px; color: #111827; font-size: 8px; }
    .pax-table { width: 100%; border-collapse: collapse; border: 1px solid #e6e8ee; border-radius: 8px; overflow: hidden; page-break-inside: avoid; }
    .pax-table th { background: #fbfcfe; color: #667085; font-size: 7px; font-weight: 800; text-transform: uppercase; text-align: left; padding: 8px 9px; border-bottom: 1px solid #e6e8ee; }
    .pax-table td { padding: 8px 9px; border-top: 1px solid #eef0f4; font-size: 8px; vertical-align: top; }
    .two-col { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 14px; }
    .two-col td { vertical-align: top; width: 50%; }
    .card { border: 1px solid #e6e8ee; border-radius: 10px; overflow: hidden; page-break-inside: avoid; }
    .card-head { padding: 9px 12px; background: #fbfcfe; color: #111827; font-size: 9px; font-weight: 800; border-bottom: 1px solid #eef0f4; }
    .card-body { padding: 10px 12px; }
    .row { width: 100%; border-collapse: collapse; }
    .row td { padding: 5px 0; border-bottom: 1px solid #f1f3f7; font-size: 8px; }
    .row td:first-child { color: #667085; }
    .row td:last-child { text-align: right; color: #111827; font-weight: 800; }
    .total td { border-bottom: none; padding-top: 8px; font-size: 10px; font-weight: 800; }
    .reminders { margin-top: 12px; padding: 11px 13px; background: #f7f7ff; border: 1px solid #d7d8ff; border-radius: 10px; color: #30364a; font-size: 8px; page-break-inside: avoid; }
    .reminders strong { color: #303191; }
    .footer { margin-top: 14px; padding-top: 10px; border-top: 1px solid #e6e8ee; color: #98a2b3; font-size: 7.5px; }
    .footer table { width: 100%; border-collapse: collapse; }
    .right { text-align: right; }
</style>
</head>
<body>
<div class="page">
    <div class="hero">
        <table>
            <tr>
                <td>
                    <div class="brand">TravelWheel</div>
                    <div class="brand-sub">Electronic ticket</div>
                </td>
                <td class="right">
                    <span class="status {{ $isTicketed ? '' : 'pending' }}">{{ $isTicketed ? 'Ticketed' : 'Confirmed' }}</span>
                </td>
            </tr>
        </table>
        <div class="hero-title">{{ $isTicketed ? 'E-ticket issued' : 'Booking confirmed' }}</div>
        <div class="hero-copy">
            {{ $isTicketed ? 'This document is your official e-ticket. Present it at airport check-in with a valid ID or passport.' : 'Your booking is confirmed and ticketing is in progress. A final ticket will be sent once issued.' }}
        </div>
    </div>

    <table class="meta-grid">
        <tr>
            <td>
                <div class="label">Booking reference</div>
                <div class="value mono primary">{{ $bookingRef }}</div>
            </td>
            <td>
                <div class="label">Airline PNR</div>
                <div class="value mono primary">{{ $ticketPNR ?? '-' }}</div>
            </td>
            <td class="last">
                <div class="label">Trip</div>
                <div class="value">{{ $tripLabel ?? 'Flight' }}</div>
                <div style="margin-top:2px;color:#667085;font-size:8px;">{{ $cabin ?? '-' }}</div>
            </td>
        </tr>
    </table>

    @if(!$isTicketed)
    <div class="notice">
        Ticketing is in progress. Your ticket will be emailed to {{ $contactEmail ?: 'your registered email' }} shortly.
    </div>
    @endif

    @if(!empty($outboundSegments))
        <div class="section-title">Outbound flight{{ count($outboundSegments) > 1 ? 's' : '' }}</div>
        @foreach($outboundSegments as $seg)
            @include('pdf.partials.flight-segment', ['seg' => $seg, 'badge' => 'outbound'])
        @endforeach
    @endif

    @if(!empty($returnSegments))
        <div class="section-title">Return flight{{ count($returnSegments) > 1 ? 's' : '' }}</div>
        @foreach($returnSegments as $seg)
            @include('pdf.partials.flight-segment', ['seg' => $seg, 'badge' => 'return'])
        @endforeach
    @endif

    @if(!empty($multiLegs))
        @foreach($multiLegs as $li => $leg)
            <div class="section-title">Leg {{ $li + 1 }}: {{ $leg['from'] ?? '' }} to {{ $leg['to'] ?? '' }}</div>
            @foreach($leg['segments'] ?? [] as $seg)
                @include('pdf.partials.flight-segment', ['seg' => $seg, 'badge' => 'leg'])
            @endforeach
        @endforeach
    @endif

    @if(!empty($passengers))
    <div class="section-title">Passenger details</div>
    <table class="pax-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Passenger</th>
                <th>Type</th>
                <th>Passport</th>
                <th>E-ticket</th>
            </tr>
        </thead>
        <tbody>
            @foreach($passengers as $i => $pax)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td><strong>{{ trim(($pax['title'] ?? '') . ' ' . strtoupper($pax['first_name'] ?? '') . ' ' . strtoupper($pax['last_name'] ?? '')) }}</strong></td>
                <td>{{ match($pax['type'] ?? 'ADT') { 'ADT' => 'Adult', 'CHD' => 'Child', 'INF' => 'Infant', default => 'Passenger' } }}</td>
                <td class="mono">{{ $pax['passport_no'] ?? '-' }}</td>
                <td class="mono primary">{{ $pax['eticket'] ?? ($isTicketed ? '-' : 'Pending') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <table class="two-col">
        <tr>
            <td style="padding-right:7px;">
                <div class="card">
                    <div class="card-head">Fare summary</div>
                    <div class="card-body">
                        <table class="row">
                            @foreach($fareBreakdown ?? [] as $fb)
                                @php
                                    $ptLabel = match($fb['passengerType'] ?? 'ADT') { 'ADT' => 'Adult', 'CHD' => 'Child', 'INF' => 'Infant', default => 'Passenger' };
                                    $qty = $fb['qty'] ?? 1;
                                @endphp
                                <tr>
                                    <td>{{ $ptLabel }} x {{ $qty }}</td>
                                    <td>{{ $currencySymbol }}{{ number_format((float)($fb['totalFare'] ?? 0) * $qty, 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="total">
                                <td>Total paid</td>
                                <td>{{ $currencySymbol }}{{ number_format((float)$totalAmount, 2) }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </td>
            <td style="padding-left:7px;">
                <div class="card">
                    <div class="card-head">Contact and support</div>
                    <div class="card-body">
                        <table class="row">
                            <tr><td>Email</td><td>{{ $contactEmail ?: '-' }}</td></tr>
                            <tr><td>Phone</td><td>{{ $contactPhone ?: '-' }}</td></tr>
                            <tr><td>Support</td><td>support@travelwheel.ng</td></tr>
                            <tr><td>Hotline</td><td>+2348056265618</td></tr>
                        </table>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="reminders">
        <strong>Travel reminders:</strong> Arrive 2 hours before domestic flights and 3 hours before international flights. Carry a valid ID or passport. Names must match the travel document exactly. Check airline baggage rules before departure.
    </div>

    <div class="footer">
        <table>
            <tr>
                <td>TravelWheel Limited. This e-ticket was generated for booking {{ $bookingRef }}.</td>
                <td class="right">Generated {{ now()->timezone('Africa/Lagos')->format('d M Y H:i') }}</td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>
