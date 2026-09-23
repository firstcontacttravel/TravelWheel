@php
    $c = config('brand.colors');

    $allSegments = collect([$outboundSegments ?? [], $returnSegments ?? []])
        ->merge(collect($multiLegs ?? [])->pluck('segments'))
        ->flatten(1)
        ->filter(fn ($s) => is_array($s))
        ->values();

    $first = $allSegments->first() ?? [];
    $last = $allSegments->last() ?? [];

    // On a return trip the journey ends where it began, so the hero shows the
    // outbound destination — the place you are actually going.
    $heroEnd = ! empty($returnSegments)
        ? (collect($outboundSegments)->last() ?? $last)
        : $last;

    $departAt = ! empty($first['departDT']) ? \Carbon\Carbon::parse($first['departDT']) : null;
    $arriveAt = ! empty($heroEnd['arriveDT']) ? \Carbon\Carbon::parse($heroEnd['arriveDT']) : null;
    $journey = $departAt && $arriveAt
        ? intdiv((int) $departAt->diffInMinutes($arriveAt, false), 60).'h '
            .str_pad((string) ((int) $departAt->diffInMinutes($arriveAt, false) % 60), 2, '0', STR_PAD_LEFT).'m'
        : null;

    $outboundStops = max(0, count($outboundSegments ?? []) - 1);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>E-ticket {{ $bookingRef }}</title>
<style>@include('pdf.partials.styles')</style>
</head>
<body>

@unless ($isTicketed)
    <div class="watermark">NOT YET A TICKET</div>
@endunless

<table class="masthead">
    <tr>
        <td>
            <div class="wordmark">TravelWheel</div>
            <div class="doctype">{{ $isTicketed ? 'Electronic ticket' : 'Booking confirmation' }}</div>
        </td>
        <td class="right" style="width:120px;">
            <span class="stamp">{{ $isTicketed ? 'TICKETED' : 'CONFIRMED' }}</span>
        </td>
    </tr>
</table>

<table class="hero">
    <tr>
        <td style="width:27%;">
            <div class="code">{{ $first['from'] ?? '—' }}</div>
            <div class="place">{{ $first['fromCity'] ?? ($first['fromAirport'] ?? '') }}</div>
        </td>
        <td class="mid center">
            <div class="dur">{{ $journey ?: ($tripLabel ?? 'Flight') }}</div>
            <div class="rule"></div>
            <div class="stops">
                {{ $outboundStops === 0 ? 'Non-stop' : $outboundStops.' stop'.($outboundStops === 1 ? '' : 's') }}@if (! empty($returnSegments) && ! empty($returnSegments[0]['departDT'])) &nbsp;·&nbsp; back {{ \Carbon\Carbon::parse($returnSegments[0]['departDT'])->format('d M') }}@endif
            </div>
        </td>
        <td class="right" style="width:27%;">
            <div class="code">{{ $heroEnd['to'] ?? '—' }}</div>
            <div class="place">{{ $heroEnd['toCity'] ?? ($heroEnd['toAirport'] ?? '') }}</div>
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
            <div class="v code">{{ $ticketPNR ?: '—' }}</div>
        </td>
        <td>
            <div class="k">Trip</div>
            <div class="v">{{ $tripLabel ?? 'Flight' }}</div>
            <div class="sub">{{ $cabin ?: '—' }}</div>
        </td>
        <td class="last">
            <div class="k">Travellers</div>
            <div class="v">{{ count($passengers ?? []) ?: 1 }}</div>
            <div class="sub">{{ $airline ?: '' }}</div>
        </td>
    </tr>
</table>

@unless ($isTicketed)
    <div class="note warn">
        <strong>This is not a ticket yet.</strong>
        @if ($awaitingSupplierTicket ?? false)
            Your seat is reserved and your payment is complete. The airline issues the ticket separately; it will be
            emailed to {{ $contactEmail ?: 'your registered address' }} as soon as it is issued.
        @else
            Ticketing is in progress. Your ticket will be emailed to {{ $contactEmail ?: 'your registered address' }} shortly.
        @endif
        Do not travel on this document.
    </div>
@endunless

{{-- A multi-city booking carries its first leg in BOTH flight_snapshot
     .segments and .multiLegs[0], so rendering outbound unconditionally
     alongside the legs printed that leg twice. multiLegs wins when present. --}}
@if (! empty($multiLegs))
    @foreach ($multiLegs as $index => $leg)
        @include('pdf.partials.journey', [
            'segments' => $leg['segments'] ?? [],
            'label' => 'Leg '.($index + 1).' · '.($leg['from'] ?? '').' to '.($leg['to'] ?? ''),
        ])
    @endforeach
@else
    @if (! empty($outboundSegments))
        @include('pdf.partials.journey', [
            'segments' => $outboundSegments,
            'label' => empty($returnSegments) ? 'Your flight' : 'Outbound',
        ])
    @endif

    @if (! empty($returnSegments))
        @include('pdf.partials.journey', ['segments' => $returnSegments, 'label' => 'Return'])
    @endif
@endif

@if (! empty($passengers))
    <div class="section">
        {{ count($passengers) === 1 ? 'Traveller' : 'Travellers' }}
        <span class="count">&nbsp;Names must match the travel document exactly</span>
    </div>
    <table class="grid">
        <tr>
            <th style="width:38%;">Name</th>
            <th style="width:14%;">Type</th>
            <th style="width:22%;">Passport</th>
            <th style="width:26%;">E-ticket number</th>
        </tr>
        @foreach ($passengers as $pax)
            <tr>
                <td class="name">{{ trim(($pax['title'] ?? '').' '.strtoupper($pax['first_name'] ?? '').' '.strtoupper($pax['last_name'] ?? '')) }}</td>
                <td class="muted">{{ match ($pax['type'] ?? 'ADT') { 'ADT' => 'Adult', 'CHD' => 'Child', 'INF' => 'Infant', default => 'Passenger' } }}</td>
                <td class="mono">{{ $pax['passport_no'] ?? '—' }}</td>
                <td class="mono brand-ink">{{ $pax['eticket'] ?? ($isTicketed ? '—' : 'Pending') }}</td>
            </tr>
        @endforeach
    </table>
@endif

<table style="margin-top:16px;">
    <tr>
        <td style="width:50%;padding-right:8px;">
            <div class="section" style="margin-top:0;">Fare summary</div>
            <table class="money">
                @foreach ($fareBreakdown ?? [] as $fb)
                    @php $qty = (int) ($fb['qty'] ?? 1); @endphp
                    <tr>
                        <td>{{ match ($fb['passengerType'] ?? 'ADT') { 'ADT' => 'Adult', 'CHD' => 'Child', 'INF' => 'Infant', default => 'Passenger' } }} &times; {{ $qty }}</td>
                        <td class="amt">{{ $currencySymbol }}{{ number_format((float) ($fb['totalFare'] ?? 0) * $qty, 2) }}</td>
                    </tr>
                @endforeach
                @if (($extrasTotal ?? 0) > 0)
                    <tr>
                        <td>Extras and services</td>
                        <td class="amt">{{ $currencySymbol }}{{ number_format((float) $extrasTotal, 2) }}</td>
                    </tr>
                @endif
                <tr class="total">
                    <td>Total paid</td>
                    <td class="amt">{{ $currencySymbol }}{{ number_format((float) $totalAmount, 2) }}</td>
                </tr>
            </table>
        </td>
        <td style="width:50%;padding-left:8px;">
            <div class="section" style="margin-top:0;">Contact</div>
            <table class="pair">
                <tr>
                    <td><div class="k">Booked by</div><div class="v">{{ $contactEmail ?: '—' }}</div></td>
                </tr>
                <tr>
                    <td><div class="k">Phone</div><div class="v">{{ $contactPhone ?: '—' }}</div></td>
                </tr>
                <tr>
                    <td>
                        <div class="k">TravelWheel support</div>
                        <div class="v">{{ config('brand.support_email') }}</div>
                        <div class="k" style="margin-top:2px;">{{ config('brand.support_phone') }}</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div class="note">
    <strong>Before you travel.</strong>
    Arrive 3 hours before international flights and 2 hours before domestic ones. Carry the passport or ID used to
    book. Check the airline's baggage rules and any visa or health requirements for your destination before you
    leave. Quote {{ $bookingRef }} whenever you contact us about this trip.
</div>

<div class="footer">
    <table>
        <tr>
            <td>TravelWheel &nbsp;·&nbsp; {{ config('brand.address') }}</td>
            <td class="right">{{ $isTicketed ? 'E-ticket' : 'Booking confirmation' }} {{ $bookingRef }} &nbsp;·&nbsp; issued {{ now()->timezone('Africa/Lagos')->format('d M Y H:i') }} WAT</td>
        </tr>
    </table>
</div>

</body>
</html>
