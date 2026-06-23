@php
    $badge = $badge ?? 'outbound';
    $badgeLabel = match($badge) {
        'return' => 'Return',
        'leg' => 'Leg',
        default => 'Outbound',
    };
    $from = $seg['from'] ?? $seg['dep_iata'] ?? $seg['airportOriginCode'] ?? '-';
    $to = $seg['to'] ?? $seg['arr_iata'] ?? $seg['airportDestinationCode'] ?? '-';
    $fromCity = $seg['fromCity'] ?? $seg['dep_city'] ?? $seg['fromAirport'] ?? '';
    $toCity = $seg['toCity'] ?? $seg['arr_city'] ?? $seg['toAirport'] ?? '';
    $departTime = !empty($seg['departDT'])
        ? \Carbon\Carbon::parse($seg['departDT'])->timezone('Africa/Lagos')->format('H:i')
        : ($seg['departTime'] ?? $seg['dep_time'] ?? '-');
    $arriveTime = !empty($seg['arriveDT'])
        ? \Carbon\Carbon::parse($seg['arriveDT'])->timezone('Africa/Lagos')->format('H:i')
        : ($seg['arriveTime'] ?? $seg['arr_time'] ?? '-');
    $date = !empty($seg['departDT'])
        ? \Carbon\Carbon::parse($seg['departDT'])->timezone('Africa/Lagos')->format('D, d M Y')
        : ($seg['departDate'] ?? $seg['date'] ?? '-');
    $duration = $seg['durationLabel'] ?? $seg['duration'] ?? null;
    if (is_numeric($duration)) {
        $duration = floor(((int) $duration) / 60) . 'h ' . (((int) $duration) % 60) . 'm';
    }
    $flightNo = $seg['flightNo'] ?? $seg['flight_number'] ?? trim(($seg['airlineCode'] ?? '') . ' ' . ($seg['flightNumber'] ?? ''));
    $airline = $seg['airline'] ?? $seg['airline_name'] ?? $seg['operatingAirline'] ?? 'Airline';
    $cabin = \App\Support\FlightDisplay::cabin($seg ?? []);
@endphp
<div class="flight-card">
    <table class="flight-head">
        <tr>
            <td>
                <span class="leg-badge {{ $badge === 'return' ? 'return' : ($badge === 'leg' ? 'leg' : '') }}">{{ $badgeLabel }}</span>
                <span class="flight-no">{{ $flightNo ?: '-' }}</span>
                <div class="flight-airline">{{ $airline }}</div>
            </td>
            <td class="flight-date">{{ $date }}</td>
        </tr>
    </table>

    <table class="route-table">
        <tr>
            <td class="route-point">
                <div class="time">{{ $departTime }}</div>
                <div class="iata">{{ $from }}</div>
                <div class="city">{{ $fromCity }}</div>
            </td>
            <td class="route-mid">
                <div class="duration">{{ $duration ?: '-' }}</div>
                <div class="route-line"></div>
                <div class="stops">{{ isset($seg['stops']) ? ((int) $seg['stops'] === 0 ? 'Non stop' : $seg['stops'] . ' stop(s)') : 'Flight' }}</div>
            </td>
            <td class="route-point right">
                <div class="time">{{ $arriveTime }}</div>
                <div class="iata">{{ $to }}</div>
                <div class="city">{{ $toCity }}</div>
            </td>
        </tr>
    </table>

    <table class="flight-meta-row">
        <tr>
            <td><span>Cabin</span><strong>{{ $cabin }}</strong></td>
            <td><span>Baggage</span><strong>{{ $seg['baggage'] ?? $seg['baggageInfo'] ?? '-' }}</strong></td>
            <td><span>Aircraft</span><strong>{{ $seg['equipment'] ?? '-' }}</strong></td>
            <td><span>Terminal</span><strong>{{ $seg['terminal'] ?? '-' }}</strong></td>
        </tr>
    </table>
</div>
