{{--
    Partial: pdf/partials/flight-segment.blade.php
    Variables:
      $seg   — segment data array (see keys below)
      $badge — 'outbound' | 'return' | 'leg'

    Expected $seg keys:
      flight_number, airline_name, cabin_code,
      dep_iata, dep_city, dep_time,
      arr_iata, arr_city, arr_time,
      duration, stops,
      date,
      terminal, gate, baggage, seat
--}}
<div class="flight-card">

    {{-- Top row: badge + flight number + date --}}
    <div class="fc-top">
        <table>
            <tr>
                <td>
                    <span class="leg-badge badge-{{ $badge }}">{{ ucfirst($badge) }}</span>
                    <span class="fnum">{{ $seg['flight_number'] ?? '—' }}</span>
                    <span class="fmeta">{{ $seg['airline_name'] ?? '' }} &nbsp;·&nbsp; {{ $seg['cabin_code'] ?? '' }}</span>
                </td>
                <td class="fdate">{{ !empty($seg['date']) ? \Carbon\Carbon::parse($seg['date'])->format('D, d M Y') : '—' }}</td>
            </tr>
        </table>
    </div>

    {{-- Route row: origin → destination --}}
    <div class="route-row">
        <table class="route-table">
            <tr>
                {{-- Departure --}}
                <td style="width:28%;">
                    <div class="iata">{{ $seg['dep_iata'] ?? '—' }}</div>
                    <div class="city">{{ $seg['dep_city'] ?? '' }}</div>
                    <div class="dep-arr-time">{{ $seg['dep_time'] ?? '—' }}</div>
                </td>

                {{-- Arrow + duration --}}
                <td class="arrow-col">
                    <div class="dur-label">{{ $seg['duration'] ?? '' }}</div>
                    <div class="arrow-line"></div>
                    <div class="stops-label">{{ $seg['stops'] ?? 'Non-stop' }}</div>
                </td>

                {{-- Arrival --}}
                <td style="width:28%; text-align:right;">
                    <div class="iata">{{ $seg['arr_iata'] ?? '—' }}</div>
                    <div class="city">{{ $seg['arr_city'] ?? '' }}</div>
                    <div class="dep-arr-time">{{ $seg['arr_time'] ?? '—' }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Footer detail row: terminal, gate, baggage, seat --}}
    <div class="fc-foot">
        <table>
            <tr>
                <td>
                    <div class="dl">Terminal</div>
                    <div class="dv">{{ $seg['terminal'] ?? '—' }}</div>
                </td>
                <td>
                    <div class="dl">Gate</div>
                    <div class="dv">{{ $seg['gate'] ?? '—' }}</div>
                </td>
                <td>
                    <div class="dl">Baggage</div>
                    <div class="dv">{{ $seg['baggage'] ?? '—' }}</div>
                </td>
                <td>
                    <div class="dl">Seat</div>
                    <div class="dv">{{ $seg['seat'] ?? '—' }}</div>
                </td>
            </tr>
        </table>
    </div>

</div>