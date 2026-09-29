{{--
    One leg of a journey drawn as a spine with a node at every airport.

    @param array  $segments  segments for this leg
    @param string $label     section heading, e.g. "Outbound" or "Leg 2"

    Accepts both segment shapes in the codebase: the raw flight_snapshot keys
    the e-ticket passes (departDT, flightNo, equipment) and the normalised ones
    ItineraryPdfService produces (depart_at as a Carbon, flight_number,
    aircraft). One component then serves both documents, which is what stops
    them drifting apart again.

    The previous layout drew every segment as a separate bordered card with
    nothing between them, so on LOS-AMS-LHR a traveller saw "arrive AMS 06:10"
    and "depart AMS 08:25" as two unrelated flights. The connection time — the
    number people actually worry about — could not be expressed at all. It is
    computed here.
--}}
@php
    /** Either shape, as a Carbon or null. */
    $at = function (array $segment, string $which): ?\Carbon\Carbon {
        $value = $segment[$which === 'depart' ? 'depart_at' : 'arrive_at']
            ?? $segment[$which === 'depart' ? 'departDT' : 'arriveDT']
            ?? null;

        if ($value instanceof \Carbon\Carbon) {
            return $value;
        }

        return filled($value) ? \Carbon\Carbon::parse($value) : null;
    };

    $pick = fn (array $segment, array $keys, $default = null) => collect($keys)
        ->map(fn ($key) => $segment[$key] ?? null)
        ->first(fn ($value) => filled($value) && $value !== '-') ?? $default;

    $minutesLabel = fn (int $minutes): string =>
        intdiv($minutes, 60).'h '.str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT).'m';
@endphp

<div class="section">{{ $label }}</div>

@foreach ($segments as $index => $seg)
    @php
        $from = $pick($seg, ['from'], '—');
        $to = $pick($seg, ['to'], '—');
        $departAt = $at($seg, 'depart');
        $arriveAt = $at($seg, 'arrive');

        $duration = $pick($seg, ['duration', 'durationLabel']);
        $durationLabel = is_numeric($duration) ? $minutesLabel((int) $duration) : ($duration ?: null);

        $previousArrival = $index > 0 ? $at($segments[$index - 1], 'arrive') : null;
        $gap = $previousArrival && $departAt ? (int) $previousArrival->diffInMinutes($departAt, false) : 0;
    @endphp

    @if ($gap > 0)
        <div class="layover">{{ $minutesLabel($gap) }} connection in {{ $pick($seg, ['from_city', 'fromCity'], $from) }}</div>
    @endif

    <table class="leg">
        <tr>
            <td class="spine"><div class="dot"></div></td>
            <td class="stop-time">{{ $departAt?->format('H:i') ?? $pick($seg, ['departTime'], '—') }}</td>
            <td class="stop-body">
                <div class="stop-place">{{ $from }} &nbsp; {{ $pick($seg, ['from_city', 'fromCity'], '') }}</div>
                <div class="stop-airport">{{ $pick($seg, ['from_airport', 'fromAirport'], '') }}@if (filled($seg['terminal'] ?? null)) · Terminal {{ $seg['terminal'] }}@endif</div>
            </td>
            <td class="right stop-day nowrap" style="width:92px;">{{ $departAt?->format('D, d M Y') }}</td>
        </tr>

        <tr>
            <td class="spine">
                <table class="rail"><tr><td class="pad"></td><td class="ink"></td></tr></table>
            </td>
            <td colspan="3" style="padding:0;">
                <div class="carrier">
                    <table>
                        <tr>
                            <td>
                                <span class="name">{{ $pick($seg, ['airline'], 'Airline') }}</span>
                                <span class="no">&nbsp; {{ $pick($seg, ['flight_number', 'flightNo'], '') }}</span>
                            </td>
                            <td class="right muted nowrap" style="width:92px;font-size:8px;">{{ $durationLabel }}</td>
                        </tr>
                    </table>
                    <table class="facts">
                        <tr>
                            <td style="width:25%;">Cabin<strong>{{ $pick($seg, ['cabin'], null) ?: \App\Support\FlightDisplay::cabin($seg) }}</strong></td>
                            <td style="width:25%;">Checked baggage<strong>{{ $pick($seg, ['baggage'], '—') }}</strong></td>
                            <td style="width:25%;">Aircraft<strong>{{ $pick($seg, ['aircraft', 'equipment'], '—') }}</strong></td>
                            <td style="width:25%;">Booking class<strong>{{ $pick($seg, ['booking_class', 'resBookCode'], '—') }}</strong></td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>

        <tr>
            <td class="spine"><div class="dot {{ $loop->last ? '' : 'small' }}"></div></td>
            <td class="stop-time">{{ $arriveAt?->format('H:i') ?? $pick($seg, ['arriveTime'], '—') }}</td>
            <td class="stop-body">
                <div class="stop-place">{{ $to }} &nbsp; {{ $pick($seg, ['to_city', 'toCity'], '') }}</div>
                <div class="stop-airport">{{ $pick($seg, ['to_airport', 'toAirport'], '') }}</div>
            </td>
            <td class="right stop-day nowrap" style="width:92px;">{{ $arriveAt?->format('D, d M Y') }}</td>
        </tr>
    </table>
@endforeach
