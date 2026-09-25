@php
    $c = config('brand.colors');
    $statusTone = $isTicketed ? $c['success'] : $c['warning'];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>{{ $documentTitle }} {{ $bookingRef }}</title>
<style>@include('pdf.partials.styles')</style>
</head>
<body>

@if ($showWatermark)
    <div class="watermark">{{ $watermarkLabel }}</div>
@endif

<table class="masthead">
    <tr>
        <td>
            <div class="wordmark">TravelWheel</div>
            <div class="doctype">{{ $documentTitle }}</div>
        </td>
        <td class="right" style="width:150px;">
            <span class="stamp">{{ strtoupper($statusLabel) }}</span>
        </td>
    </tr>
</table>

<table class="hero">
    <tr>
        <td style="width:27%;">
            <div class="code">{{ $origin['from'] ?? '—' }}</div>
            <div class="place">{{ $origin['from_airport'] ?: ($origin['from_city'] ?? '') }}</div>
        </td>
        <td class="mid center">
            <div class="dur">{{ $journeyDuration }}</div>
            <div class="rule"></div>
            <div class="stops">{{ $totalStops === 0 ? 'Non-stop' : $totalStops.' stop'.($totalStops === 1 ? '' : 's') }} &nbsp;·&nbsp; {{ $tripLabel }}</div>
        </td>
        <td class="right" style="width:27%;">
            <div class="code">{{ $destination['to'] ?? '—' }}</div>
            <div class="place">{{ $destination['to_airport'] ?: ($destination['to_city'] ?? '') }}</div>
        </td>
    </tr>
</table>

<table class="refs">
    <tr>
        <td>
            <div class="k">Booking reference</div>
            <div class="v code">{{ $bookingRef }}</div>
        </td>
        <td>
            <div class="k">Airline reference (PNR)</div>
            @if ($showTicketData)
                <div class="v code">{{ $ticketPNR ?: '—' }}</div>
            @else
                <div class="v muted" style="font-size:9px;">Issued with the ticket</div>
            @endif
        </td>
        <td>
            <div class="k">{{ $isTicketed ? 'Ticket issued' : (($awaitingSupplierTicket ?? false) ? 'Payment' : 'Hold expires') }}</div>
            <div class="v" style="font-size:10px;">
                @if ($awaitingSupplierTicket ?? false)
                    Received
                @else
                    {{ ($isTicketed ? $issuedAt : $holdUntil)?->timezone('Africa/Lagos')->format('d M Y') ?? '—' }}
                @endif
            </div>
            <div class="sub">Departs {{ $travelDate?->format('d M Y') ?? '—' }}</div>
        </td>
        <td class="last">
            <div class="k">Airline</div>
            <div class="v" style="font-size:10px;">{{ $airline }}</div>
            <div class="sub">{{ $flightNumbers ?: '—' }}</div>
        </td>
    </tr>
</table>

@if ($showWatermark)
    <div class="note warn">
        <strong>This is an itinerary, not a ticket.</strong>
        It confirms what has been booked but is not valid for travel and does not confirm ticket issuance. The
        airline reference and e-ticket numbers appear only once ticketing is complete.
    </div>
@endif

@foreach ($segmentGroups as $group)
    @include('pdf.partials.journey', [
        'segments' => $group['segments'],
        'label' => \Illuminate\Support\Str::of($group['label'])->lower()->ucfirst()->toString(),
    ])
@endforeach

@if ($passengers)
    <div class="section">
        {{ count($passengers) === 1 ? 'Traveller' : 'Travellers' }}
        <span class="count">&nbsp;Names must match the travel document exactly</span>
    </div>
    <table class="grid">
        <tr>
            <th style="width:30%;">Name</th>
            <th style="width:13%;">Type</th>
            <th style="width:15%;">Date of birth</th>
            <th style="width:16%;">Nationality</th>
            <th style="width:26%;">{{ $showTicketData ? 'E-ticket number' : 'Ticket' }}</th>
        </tr>
        @foreach ($passengers as $passenger)
            @php
                $name = trim(($passenger['title'] ?? '').' '.($passenger['first_name'] ?? '').' '.($passenger['last_name'] ?? ''));
                $dob = filled($passenger['date_of_birth'] ?? null)
                    ? \Carbon\Carbon::parse($passenger['date_of_birth'])->format('d M Y')
                    : '—';
            @endphp
            <tr>
                <td class="name">{{ strtoupper($name ?: 'Passenger') }}</td>
                <td class="muted">{{ match (strtoupper((string) ($passenger['type'] ?? 'ADT'))) { 'CHD' => 'Child', 'INF' => 'Infant', default => 'Adult' } }}</td>
                <td>{{ $dob }}</td>
                <td>{{ $passenger['nationality'] ?? '—' }}</td>
                <td class="mono {{ $showTicketData ? 'brand-ink' : 'muted' }}">{{ $showTicketData ? ($passenger['eticket'] ?? '—') : 'Not issued' }}</td>
            </tr>
        @endforeach
    </table>

    {{-- Cabin, booking status and the contact address are properties of the
         booking, not of each traveller. The old layout repeated all three
         inside every passenger card, so a three-passenger itinerary printed
         the same email address and the same status three times over. --}}
    <table class="pair" style="margin-top:8px;">
        <tr>
            <td style="width:34%;"><div class="k">Cabin</div><div class="v">{{ $cabin }}</div></td>
            <td style="width:33%;"><div class="k">Booking status</div><div class="v" style="color:{{ $statusTone }};">{{ $statusLabel }}</div></td>
            <td class="last" style="width:33%;"><div class="k">Booking contact</div><div class="v">{{ $contactEmail ?: '—' }}</div></td>
        </tr>
    </table>
@endif

<div class="note">
    <strong>Before you travel.</strong>
    Arrive 3 hours before international flights and 2 hours before domestic ones. Carry the passport or ID used to
    book, and check any visa or health requirements for your destination. Quote {{ $bookingRef }} whenever you
    contact us about this trip &mdash; {{ config('brand.support_email') }}, {{ config('brand.support_phone') }}.
</div>

<div class="footer">
    <table>
        <tr>
            <td>TravelWheel &nbsp;·&nbsp; {{ config('brand.address') }}</td>
            <td class="right">{{ $documentTitle }} {{ $bookingRef }} &nbsp;·&nbsp; generated {{ $generatedAt->format('d M Y H:i') }} WAT</td>
        </tr>
    </table>
</div>

</body>
</html>
