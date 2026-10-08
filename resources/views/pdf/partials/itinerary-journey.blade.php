{{--
    One journey of the itinerary: outbound, return, or one multi-city leg.

    @param array  $group  ['label', 'segments', 'duration'] from ItineraryPdfService

    Times print as the supplier gives them, local to each airport. They are
    never shifted into Lagos time, and a connection is measured at one
    airport, so subtracting its two local times is safe.
--}}
@php
    $segments = $group['segments'];
    $label = \Illuminate\Support\Str::of($group['label'])->lower()->ucfirst()->toString();
    $connections = max(0, count($segments) - 1);
    $minutes = fn (int $total): string => intdiv($total, 60).'h '.str_pad((string) ($total % 60), 2, '0', STR_PAD_LEFT).'m';
    $city = fn (array $segment, string $side): string => trim((string) preg_replace('/\s*\([A-Z]{3}\)\s*$/', '', (string) ($segment[$side.'_city'] ?? ''))) ?: (string) ($segment[$side] ?? '');
    $shown = fn ($value): bool => filled($value) && $value !== '-';
@endphp

<div class="journey">
    {{-- Each flight is kept whole on one page, together with what introduces
         it: the journey heading for the first, the connection for the rest. --}}
    @foreach ($segments as $index => $seg)
    <div class="keep">
        @if ($index === 0)
            <table class="journey-head">
                <tr>
                    <td>
                        <span class="journey-name">{{ $label }}</span>
                        <span class="journey-date">{{ $seg['depart_at']?->format('D d M Y') }}</span>
                    </td>
                    <td class="right journey-total">
                        @if ($group['duration'])
                            {{ $group['duration'] }} total,
                        @endif
                        {{ $connections === 0 ? 'direct' : $connections.' '.($connections === 1 ? 'connection' : 'connections') }}
                    </td>
                </tr>
            </table>
        @endif

        @php
            $previous = $segments[$index - 1] ?? null;
            $gap = $previous && $previous['arrive_at'] && $seg['depart_at']
                ? (int) $previous['arrive_at']->diffInMinutes($seg['depart_at'], false)
                : 0;
            $dayShift = $seg['depart_at'] && $seg['arrive_at']
                ? (int) $seg['depart_at']->copy()->startOfDay()->diffInDays($seg['arrive_at']->copy()->startOfDay(), false)
                : 0;
            $cabinLine = collect([
                $seg['cabin'] ?? null,
                $shown($seg['booking_class'] ?? null) ? 'class '.$seg['booking_class'] : null,
            ])->filter()->implode(', ');
            $details = collect([
                'Aircraft' => $seg['aircraft'] ?? null,
                'Checked baggage' => $seg['baggage'] ?? null,
                'Cabin baggage' => $seg['carry_on'] ?? null,
                'Fare basis' => $seg['fare_basis'] ?? null,
            ])->filter($shown);
        @endphp

        @if ($gap > 0)
            <div class="connection">
                <strong>{{ $minutes($gap) }} connection</strong> in {{ $city($seg, 'from') }} ({{ $seg['from'] }}). Check whether you need to collect bags or a transit visa.
            </div>
        @endif

        <table class="flight">
            <tr class="flight-carrier">
                <td colspan="2">
                    <span class="carrier-name">{{ $seg['airline'] }}</span>
                    <span class="carrier-no">{{ $seg['flight_number'] }}</span>
                </td>
                <td class="right carrier-cabin">{{ $cabinLine }}</td>
            </tr>
            <tr class="flight-route">
                <td style="width:41%;">
                    <div class="time">{{ $seg['depart_at']?->format('H:i') ?? '--:--' }}</div>
                    <div class="place"><span class="code mono">{{ $seg['from'] }}</span>&nbsp; {{ $city($seg, 'from') }}</div>
                    <div class="airport">{{ $seg['from_airport'] }}@if ($shown($seg['from_terminal'] ?? null)), Terminal {{ $seg['from_terminal'] }}@endif</div>
                    <div class="date">{{ $seg['depart_at']?->format('D d M Y') }}</div>
                </td>
                <td class="center leg-mid" style="width:18%;">
                    <div class="leg-duration">{{ $shown($seg['duration'] ?? null) ? $seg['duration'] : '' }}</div>
                    <div class="leg-line"></div>
                    <div class="leg-stops">{{ ($seg['stops'] ?? 0) > 0 ? $seg['stops'].' technical stop'.($seg['stops'] > 1 ? 's' : '') : 'Non-stop' }}</div>
                </td>
                <td class="right" style="width:41%;">
                    <div class="time">{{ $seg['arrive_at']?->format('H:i') ?? '--:--' }}@if ($dayShift > 0)<span class="day-shift">&nbsp;+{{ $dayShift }}</span>@endif</div>
                    <div class="place">{{ $city($seg, 'to') }} &nbsp;<span class="code mono">{{ $seg['to'] }}</span></div>
                    <div class="airport">{{ $seg['to_airport'] }}@if ($shown($seg['to_terminal'] ?? null)), Terminal {{ $seg['to_terminal'] }}@endif</div>
                    <div class="date">{{ $seg['arrive_at']?->format('D d M Y') }}</div>
                </td>
            </tr>
            @if ($details->isNotEmpty())
                <tr>
                    <td colspan="3" style="padding:0;">
                        <table class="flight-details">
                            <tr>
                                @foreach ($details as $name => $value)
                                    <td>
                                        <div class="label">{{ $name }}</div>
                                        <div class="value">{{ $value }}</div>
                                    </td>
                                @endforeach
                                @for ($i = $details->count(); $i < 4; $i++)
                                    <td></td>
                                @endfor
                            </tr>
                        </table>
                    </td>
                </tr>
            @endif
        </table>
    </div>
    @endforeach
</div>
