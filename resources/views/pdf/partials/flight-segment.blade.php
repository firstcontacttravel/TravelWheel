<div class="flight-card">
    <div class="flight-card-top">
        <table>
            <tr>
                <td>
                    @php
                        $badgeClass = ($badge ?? 'outbound') === 'return'
                            ? 'badge-return'
                            : (($badge ?? 'outbound') === 'leg' ? 'badge-leg' : 'badge-outbound');
                        $badgeLabel = ($badge ?? 'outbound') === 'return'
                            ? 'Return'
                            : (($badge ?? 'outbound') === 'leg' ? 'Leg' : 'Outbound');
                    @endphp
                    <span class="leg-badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                    <span class="flight-num">{{ $seg['flightNo'] ?? (($seg['airlineCode'] ?? '') . ($seg['flightNumber'] ?? '')) }}</span>
                    <div class="flight-meta">
                        {{ $seg['airline'] ?? ($seg['operatingAirline'] ?? 'Airline') }}
                    </div>
                </td>
                <td style="text-align:right;">
                    <div class="flight-date">{{ $seg['departDate'] ?? (!empty($seg['departDT']) ? \Carbon\Carbon::parse($seg['departDT'])->format('D, d M Y') : '—') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="route-row">
        <table class="route-table">
            <tr>
                <td style="width:30%;">
                    <div class="iata">{{ $seg['from'] ?? '—' }}</div>
                    <div class="city-name">{{ $seg['fromCity'] ?? ($seg['fromAirport'] ?? '') }}</div>
                    <div class="dep-arr-time">{{ !empty($seg['departDT']) ? \Carbon\Carbon::parse($seg['departDT'])->timezone('Africa/Lagos')->format('H:i') : ($seg['departTime'] ?? '—') }}</div>
                </td>

                <td class="arrow-col">
                    <div class="duration-label">{{ $seg['durationLabel'] ?? (isset($seg['duration']) ? floor(((int) $seg['duration']) / 60) . 'h ' . (((int) $seg['duration']) % 60) . 'm' : '—') }}</div>
                    <div class="plane-glyph">✈</div>
                    <div class="arrow-line"></div>
                    <div class="stops-label">{{ isset($seg['stops']) ? ((int) $seg['stops'] === 0 ? 'Non-stop' : ((int) $seg['stops'] . ' stop(s)')) : 'Flight' }}</div>
                </td>

                <td style="width:30%;text-align:right;">
                    <div class="iata">{{ $seg['to'] ?? '—' }}</div>
                    <div class="city-name">{{ $seg['toCity'] ?? ($seg['toAirport'] ?? '') }}</div>
                    <div class="dep-arr-time">{{ !empty($seg['arriveDT']) ? \Carbon\Carbon::parse($seg['arriveDT'])->timezone('Africa/Lagos')->format('H:i') : ($seg['arriveTime'] ?? '—') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="flight-footer">
        <table style="width:100%;">
            <tr>
                <td class="detail-cell">
                    <div class="detail-lbl">From Airport</div>
                    <div class="detail-val">{{ $seg['fromAirport'] ?? ($seg['from'] ?? '—') }}</div>
                </td>
                <td class="detail-cell">
                    <div class="detail-lbl">To Airport</div>
                    <div class="detail-val">{{ $seg['toAirport'] ?? ($seg['to'] ?? '—') }}</div>
                </td>
                <td class="detail-cell">
                    <div class="detail-lbl">Cabin</div>
                    <div class="detail-val">{{ \App\Support\FlightDisplay::cabin($seg ?? []) }}</div>
                </td>
                <td class="detail-cell" style="padding-right:0;">
                    <div class="detail-lbl">Equipment</div>
                    <div class="detail-val">{{ $seg['equipment'] ?? '—' }}</div>
                </td>
            </tr>
        </table>
    </div>
</div>
