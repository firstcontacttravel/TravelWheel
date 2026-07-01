<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>{{ $documentTitle }} - {{ $bookingRef }}</title>
<style>
    @page { margin: 28px 32px 34px; }
    * { box-sizing: border-box; }
    body { margin: 0; color: #101828; background: #fff; font-family: DejaVu Sans, sans-serif; font-size: 9px; line-height: 1.35; }
    .watermark { position: fixed; z-index: 1000; top: 42%; left: 4%; width: 92%; color: rgba(57,50,143,.065); font-size: 52px; font-weight: 800; text-align: center; transform: rotate(-31deg); }
    .agency-bar { width: 100%; padding-bottom: 9px; border-bottom: 2px solid #39328f; border-collapse: collapse; }
    .agency-logo { width: 116px; max-height: 34px; object-fit: contain; }
    .agency-wordmark { color: #39328f; font-size: 16px; font-weight: 800; }
    .agency-copy { color: #667085; font-size: 7px; text-align: right; }
    .agency-copy strong { color: #39328f; font-size: 8px; }
    .document-head { width: 100%; margin-top: 17px; border-collapse: collapse; }
    .document-head td { vertical-align: top; }
    .airline-logo { width: 62px; height: 48px; object-fit: contain; }
    .airline-name { font-size: 20px; font-weight: 800; line-height: 1.1; }
    .airline-meta { margin-top: 6px; color: #7a8291; font-size: 9px; }
    .trip-chip { display: inline-block; margin-top: 8px; padding: 5px 14px; color: #39328f; background: #f2f4ff; font-size: 8px; font-weight: 800; }
    .document-type { display: inline-block; min-width: 185px; padding: 10px 16px; color: #fff; background: #39328f; font-size: 10px; font-weight: 800; text-align: center; }
    .reference-label { margin-top: 11px; color: #98a2b3; font-size: 7px; font-weight: 800; text-transform: uppercase; }
    .reference { margin-top: 2px; color: #39328f; font-family: DejaVu Sans Mono, monospace; font-size: 12px; font-weight: 800; }
    .ticket-number { margin-top: 5px; color: #98a2b3; font-family: DejaVu Sans Mono, monospace; font-size: 8px; }
    .hero-route { width: 100%; margin-top: 24px; border-collapse: collapse; }
    .hero-route td { vertical-align: middle; }
    .hero-code { font-size: 31px; font-weight: 800; line-height: 1; }
    .hero-airport { margin-top: 7px; color: #344054; font-size: 8px; font-weight: 700; text-transform: uppercase; }
    .route-center { width: 56%; padding: 0 16px; text-align: center; }
    .route-duration { color: #667085; font-size: 10px; font-weight: 800; }
    .route-line { width: 100%; margin: 7px 0 5px; border-collapse: collapse; }
    .route-line td.line { width: 45%; border-top: 1px solid #cfd4dc; }
    .route-plane { width: 10%; color: #39328f; font-size: 18px; font-weight: 800; }
    .route-stops { color: #98a2b3; font-size: 8px; font-weight: 700; }
    .summary-row { width: 100%; margin-top: 20px; border-collapse: collapse; }
    .summary-row td { width: 21%; padding-right: 14px; vertical-align: top; }
    .summary-row td.status-cell { width: 37%; padding-right: 0; text-align: right; }
    .data-label { color: #98a2b3; font-size: 7px; font-weight: 800; text-transform: uppercase; letter-spacing: .3px; }
    .data-value { margin-top: 3px; font-size: 12px; font-weight: 800; }
    .data-value.primary { color: #39328f; font-family: DejaVu Sans Mono, monospace; }
    .status { display: inline-block; padding: 8px 14px; border: 1px solid {{ $statusColor }}; color: {{ $statusColor }}; background: {{ $statusBackground }}; font-size: 9px; font-weight: 800; }
    .divider { height: 4px; margin: 20px -32px 0; background: #39328f; }
    .section-title { margin: 18px 0 8px; color: #667085; font-size: 8px; font-weight: 800; text-transform: uppercase; }
    .section-title:before { content: ''; display: inline-block; width: 7px; height: 7px; margin-right: 8px; border-radius: 50%; background: #39328f; vertical-align: -1px; }
    .passenger-card { width: 100%; border-collapse: collapse; page-break-inside: avoid; }
    .passenger-card td { padding: 9px 12px; background: #f8fafc; border-bottom: 3px solid #fff; vertical-align: top; }
    .passenger-card .wide { width: 40%; }
    .passenger-card .medium { width: 30%; }
    .passenger-card .small { width: 20%; }
    .passenger-card .value { margin-top: 3px; font-size: 10px; font-weight: 800; }
    .passenger-card .accent { color: #39328f; }
    .flight-card { margin-bottom: 13px; padding: 12px 14px; border: 1px solid #e3e7ee; background: #fbfcfe; page-break-inside: avoid; }
    .flight-head { width: 100%; border-collapse: collapse; }
    .flight-head td { vertical-align: middle; }
    .segment-logo { width: 42px; height: 30px; object-fit: contain; }
    .segment-airline { font-size: 12px; font-weight: 800; }
    .segment-meta { margin-top: 2px; color: #667085; font-family: DejaVu Sans Mono, monospace; font-size: 8px; }
    .class-chip { display: inline-block; padding: 7px 12px; color: #39328f; background: #f2f4ff; font-size: 8px; font-weight: 800; }
    .segment-route { width: 100%; margin-top: 14px; border-collapse: collapse; }
    .segment-route td { vertical-align: middle; }
    .segment-point { width: 31%; }
    .segment-point.right { text-align: right; }
    .segment-time { font-size: 21px; font-weight: 800; line-height: 1; }
    .segment-date { margin-top: 6px; color: #667085; font-size: 8px; }
    .segment-place { margin-top: 4px; color: #344054; font-size: 9px; font-weight: 800; }
    .segment-center { width: 38%; padding: 0 15px; text-align: center; }
    .segment-duration { color: #667085; font-size: 9px; font-weight: 800; }
    .segment-line { margin: 7px 0 5px; border-top: 1px solid #cfd4dc; color: #39328f; font-size: 15px; line-height: 1px; }
    .segment-stops { color: #98a2b3; font-size: 7px; font-weight: 700; }
    .segment-details { margin-top: 11px; padding: 8px 9px; color: #667085; background: #f2f5f9; font-size: 7.5px; }
    .notice { margin-top: 14px; padding: 10px 12px; border: 1px solid #f2c94c; color: #7a5410; background: #fffbeb; font-size: 8px; font-weight: 700; page-break-inside: avoid; }
    .footer { position: fixed; bottom: -21px; left: 0; right: 0; padding-top: 6px; border-top: 1px solid #e4e7ec; color: #98a2b3; font-size: 6.5px; }
    .footer table { width: 100%; border-collapse: collapse; }
    .right { text-align: right; }
</style>
</head>
<body>
@if($showWatermark)<div class="watermark">{{ $watermarkLabel }}</div>@endif

<table class="agency-bar">
    <tr>
        <td>@if($travelwheelLogo)<img src="{{ $travelwheelLogo }}" class="agency-logo" alt="TravelWheel">@else<span class="agency-wordmark">TravelWheel</span>@endif</td>
        <td class="agency-copy"><strong>Booked and managed by TravelWheel</strong><br>travelwheel.ng &nbsp; | &nbsp; support@travelwheel.ng &nbsp; | &nbsp; +234 805 626 5618</td>
    </tr>
</table>

<table class="document-head">
    <tr>
        <td style="width:62px;">
            @if($airlineLogo)<img src="{{ $airlineLogo }}" class="airline-logo" alt="">@endif
        </td>
        <td>
            <div class="airline-name">{{ $airline }}</div>
            <div class="airline-meta">{{ $airlineCode ? 'IATA: '.$airlineCode.'  ·  ' : '' }}Flight: {{ $flightNumbers ?: '-' }}</div>
            <span class="trip-chip">{{ strtoupper($tripLabel) }}</span>
        </td>
        <td class="right" style="width:220px;">
            <div class="document-type">{{ $documentTitle }}</div>
            <div class="reference-label">Booking reference</div>
            <div class="reference">{{ $bookingRef }}</div>
            @if($showTicketData)
                <div class="ticket-number">Airline PNR: {{ $ticketPNR ?: '-' }}</div>
            @endif
        </td>
    </tr>
</table>

<table class="hero-route">
    <tr>
        <td style="width:22%;">
            <div class="hero-code">{{ $origin['from'] ?? '-' }}</div>
            <div class="hero-airport">{{ $origin['from_airport'] ?: ($origin['from_city'] ?? '') }}</div>
        </td>
        <td class="route-center">
            <div class="route-duration">{{ $journeyDuration }}</div>
            <table class="route-line"><tr><td class="line"></td><td class="route-plane">✈</td><td class="line"></td></tr></table>
            <div class="route-stops">{{ $totalStops === 0 ? 'Nonstop' : $totalStops.' stop'.($totalStops === 1 ? '' : 's') }}</div>
        </td>
        <td class="right" style="width:22%;">
            <div class="hero-code">{{ $destination['to'] ?? '-' }}</div>
            <div class="hero-airport">{{ $destination['to_airport'] ?: ($destination['to_city'] ?? '') }}</div>
        </td>
    </tr>
</table>

<table class="summary-row">
    <tr>
        <td>
            @if($showTicketData)
                <div class="data-label">Airline PNR</div><div class="data-value primary">{{ $ticketPNR ?: '-' }}</div>
            @else
                <div class="data-label">Document status</div><div class="data-value">Not ticketed</div>
            @endif
        </td>
        <td>
            <div class="data-label">{{ $isTicketed ? 'Issued' : 'Hold expires' }}</div>
            <div class="data-value">{{ ($isTicketed ? $issuedAt : $holdUntil)?->timezone('Africa/Lagos')->format('M j, Y') ?? '-' }}</div>
        </td>
        <td><div class="data-label">Travel date</div><div class="data-value">{{ $travelDate?->format('M j, Y') ?? '-' }}</div></td>
        <td class="status-cell"><span class="status">● {{ $statusLabel }}</span></td>
    </tr>
</table>

<div class="divider"></div>

@if($passengers)
<div class="section-title">Passenger information</div>
@foreach($passengers as $passenger)
    @php
        $name = trim(($passenger['title'] ?? '').' '.($passenger['first_name'] ?? '').' '.($passenger['last_name'] ?? ''));
        $type = match(strtoupper((string)($passenger['type'] ?? 'ADT'))) { 'CHD' => 'Child (CHD)', 'INF' => 'Infant (INF)', default => 'Adult (ADT)' };
        $ticket = $showTicketData ? ($passenger['eticket'] ?? '-') : 'Not issued';
    @endphp
    <table class="passenger-card">
        <tr>
            <td class="wide"><div class="data-label">Full name (as on passport)</div><div class="value">{{ strtoupper($name ?: 'Passenger') }}</div></td>
            <td class="medium"><div class="data-label">Nationality</div><div class="value">{{ $passenger['nationality'] ?? '-' }}</div></td>
            <td><div class="data-label">Booking status</div><div class="value" style="color:{{ $statusColor }};">● {{ $statusLabel }}</div></td>
        </tr>
        <tr>
            <td><div class="data-label">Date of birth</div><div class="value">{{ filled($passenger['date_of_birth'] ?? null) ? \Carbon\Carbon::parse($passenger['date_of_birth'])->format('M j, Y') : '-' }}</div></td>
            <td><div class="data-label">Gender</div><div class="value">{{ $passenger['gender'] ?? '-' }}</div></td>
            <td>
                @if($showTicketData)
                    <div class="data-label">E-ticket number</div><div class="value accent">{{ $ticket }}</div>
                @else
                    <div class="data-label">Ticket status</div><div class="value">Not issued</div>
                @endif
            </td>
        </tr>
        <tr>
            <td><div class="data-label">Passenger type</div><div class="value accent">{{ $type }}</div></td>
            <td><div class="data-label">Cabin class</div><div class="value">{{ $cabin }}</div></td>
            <td><div class="data-label">Email address</div><div class="value accent">{{ $contactEmail ?: '-' }}</div></td>
        </tr>
    </table>
@endforeach
@endif

@foreach($segmentGroups as $group)
    <div class="section-title">Flight segment · {{ $group['label'] }}</div>
    @foreach($group['segments'] as $segment)
        @include('pdf.partials.itinerary-segment', ['segment' => $segment])
    @endforeach
@endforeach

@if($showWatermark)
<div class="notice">This document is an itinerary summary only. It is not valid for travel and does not confirm ticket issuance. Airline PNR and e-ticket numbers will appear only after ticketing is completed.</div>
@endif

<div class="footer">
    <table><tr><td>TravelWheel Limited · Itinerary {{ $bookingRef }}</td><td class="right">Generated {{ $generatedAt->format('d M Y, H:i') }} WAT</td></tr></table>
</div>
</body>
</html>
