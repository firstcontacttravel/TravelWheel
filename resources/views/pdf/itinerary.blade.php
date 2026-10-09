{{--
    Flight itinerary PDF, attached to every flight booking email. One template
    covers every state, from a seat on hold to an issued e-ticket. The state
    decides the title, the status block and whether ticket data is shown.
    View data comes from ItineraryPdfService::buildViewData().
--}}
@php
    $c = config('brand.colors');
    $paidAwaitingTicket = ! $isTicketed && (($awaitingSupplierTicket ?? false) || $documentState === 'ticket_processing');
    $notATicket = 'This itinerary is not a ticket and is not valid for travel.';
    $statusBody = match (true) {
        $isTicketed => 'Your e-tickets are issued. Check in with the airline using the airline reference'
            .($ticketPNR ? ' '.$ticketPNR : '').' and the passport named on this booking.',
        $awaitingSupplierTicket ?? false => 'Your booking and payment are confirmed. The airline issues your ticket separately, and we will email it to you as soon as it is ready. '.$notATicket,
        $documentState === 'ticket_processing' => 'Your booking and payment are confirmed, and your ticket is being issued. We will email your e-ticket as soon as it is ready. '.$notATicket,
        $documentState === 'on_hold' => 'These seats are held but not yet paid for.'
            .($holdUntil ? ' Complete payment before '.$holdUntil->timezone('Africa/Lagos')->format('H:i, D d M Y').' (Lagos time) to keep them.' : '').' '.$notATicket,
        $documentState === 'payment_pending' => 'We are waiting for your payment to be confirmed. '.$notATicket,
        $documentState === 'travelflex_review' => 'Your TravelFlex application is being reviewed. '.$notATicket,
        $documentState === 'travelflex_approved' => 'Your TravelFlex application is approved. Your e-ticket follows once ticketing is complete. '.$notATicket,
        $documentState === 'travelflex_rejected' => 'Your TravelFlex application was not approved. Contact us to discuss other ways to keep this booking. '.$notATicket,
        $documentState === 'ticketing_required' => 'Payment is complete but no ticket has been issued. Issue it before the hold expires. '.$notATicket,
        default => 'Your booking is being processed. '.$notATicket,
    };
    $travellers = count($passengers);
    $tripType = ucfirst(strtolower(str_replace('Multi-City', 'Multi-city', $tripLabel)));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>{{ $documentTitle }} {{ $bookingRef }}</title>
<style>@include('pdf.partials.itinerary-styles')</style>
</head>
<body>

@if ($showWatermark)
    <div class="watermark">{{ $watermarkLabel }}</div>
@endif

<div class="footer">
    <table>
        <tr>
            <td>{{ config('brand.name') }}, {{ config('brand.address') }}</td>
            <td class="right">Generated {{ $generatedAt->format('d M Y, H:i') }} WAT</td>
        </tr>
    </table>
</div>

<table class="masthead">
    <tr>
        <td><div class="wordmark">{{ config('brand.name') }}</div></td>
        <td class="right">
            <div class="doc-name">{{ $documentTitle }}</div>
            <div class="doc-ref mono">{{ $bookingRef }}</div>
        </td>
    </tr>
</table>
<div class="masthead-rule"></div>

<div class="trip">
    <div class="trip-title">{{ $tripTitle }}</div>
    <div class="trip-meta">
        <span>{{ $tripType }}</span>
        @if ($tripStart)
            <span>{{ $tripStart->format('D d M Y') }}@if ($tripEnd && ! $tripEnd->isSameDay($tripStart)) to {{ $tripEnd->format('D d M Y') }}@endif</span>
        @endif
        @if ($travellers)
            <span>{{ $travellers }} {{ $travellers === 1 ? 'traveller' : 'travellers' }}</span>
        @endif
        <span>{{ $cabin }}</span>
    </div>
</div>

<div class="status keep" style="border-color:{{ $statusColor }}; background:{{ $statusBackground }};">
    <div class="status-title" style="color:{{ $statusColor }};">{{ $statusLabel }}</div>
    <div class="status-body">{{ $statusBody }}</div>
</div>

<table class="facts keep">
    <tr>
        <td class="first">
            <div class="label">Booking reference</div>
            <div class="value mono">{{ $bookingRef }}</div>
        </td>
        <td>
            <div class="label">Airline reference (PNR)</div>
            @if ($showTicketData && $ticketPNR)
                <div class="value mono">{{ $ticketPNR }}</div>
            @else
                <div class="value quiet">Issued with the ticket</div>
            @endif
        </td>
        <td>
            @if ($isTicketed)
                <div class="label">Ticket issued</div>
                <div class="value">{{ $issuedAt?->timezone('Africa/Lagos')->format('d M Y') ?? '-' }}</div>
            @elseif ($paidAwaitingTicket)
                <div class="label">Payment</div>
                <div class="value">Received</div>
            @else
                <div class="label">Hold expires</div>
                <div class="value">{{ $holdUntil?->timezone('Africa/Lagos')->format('d M Y, H:i') ?? '-' }}</div>
            @endif
        </td>
    </tr>
</table>

<div class="section">
    <div class="section-title">Flights</div>
    <div class="section-note">All times are local to the airport.</div>
</div>
@foreach ($segmentGroups as $group)
    @include('pdf.partials.itinerary-journey', ['group' => $group])
@endforeach

@if ($passengers)
    <div class="keep">
        <div class="section">
            <div class="section-title">{{ $travellers === 1 ? 'Traveller' : 'Travellers' }}</div>
            <div class="section-note">Names must match the passport used to travel.</div>
        </div>
        <table class="people">
            <tr>
                <th style="width:40%;">Name</th>
                <th style="width:14%;">Type</th>
                <th style="width:19%;">Date of birth</th>
                <th style="width:27%;">E-ticket number</th>
            </tr>
            @foreach ($passengers as $passenger)
                @php
                    $name = trim(($passenger['title'] ?? '').' '.($passenger['first_name'] ?? '').' '.($passenger['last_name'] ?? ''));
                    $dob = filled($passenger['date_of_birth'] ?? null)
                        ? \Carbon\Carbon::parse($passenger['date_of_birth'])->format('d M Y')
                        : '-';
                @endphp
                <tr>
                    <td class="name">{{ strtoupper($name ?: 'Passenger') }}</td>
                    <td>{{ match (strtoupper((string) ($passenger['type'] ?? 'ADT'))) { 'CHD' => 'Child', 'INF' => 'Infant', default => 'Adult' } }}</td>
                    <td>{{ $dob }}</td>
                    @if ($showTicketData && filled($passenger['eticket'] ?? null))
                        <td class="ticket mono">{{ $passenger['eticket'] }}</td>
                    @else
                        <td class="muted">Not issued yet</td>
                    @endif
                </tr>
            @endforeach
        </table>
    </div>
@endif

<table class="notes keep">
    <tr>
        <td>
            <div class="note-title">Before you fly</div>
            <div class="note-body">
                Arrive at the airport 3 hours before international flights and 2 hours before domestic ones.
                Carry the passport used for this booking, and check visa and health requirements for every
                country on your route, including any you only pass through.
            </div>
        </td>
        <td class="gap">
            <div class="note-title">Need help with this booking?</div>
            <div class="note-body">
                Quote <span class="mono ref">{{ $bookingRef }}</span> when you contact us.<br>
                {{ config('brand.support_email') }}<br>
                {{ config('brand.support_phone') }}, WhatsApp {{ config('brand.support_whatsapp') }}
            </div>
        </td>
    </tr>
</table>

</body>
</html>
