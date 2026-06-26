{{--
    partials/_render_leg.blade.php
    Props: $legSegs, $legLabel, $legBadgeClass, $legLayovers,
           $legStops, $legDuration, $legDate, $breakdown, $equipMap,
           $tripDetails (optional - live API data keyed by flightNo → PNR/eticket)
--}}
@php
    $legFirst = $legSegs[0] ?? [];
    $legLast  = !empty($legSegs) ? $legSegs[count($legSegs)-1] : [];
    $legLayovers = $legLayovers ?? [];

    // Build a PNR/eticket lookup from trip details if provided
    $pnrMap     = [];
    $eticketMap = [];
    if (!empty($tripDetails)) {
        $resItems = data_get($tripDetails, 'ItineraryInfo.ReservationItems', []);
        foreach ($resItems as $ri) {
            $seg = $ri['ReservationItem'] ?? $ri;
            $key = ($seg['MarketingAirlineCode'] ?? '') . ($seg['FlightNumber'] ?? '');
            $pnrMap[$key] = $seg['AirlinePNR'] ?? '';
        }
        $customers = data_get($tripDetails, 'ItineraryInfo.CustomerInfos', []);
        foreach ($customers as $ci) {
            $c = $ci['CustomerInfo'] ?? $ci;
            if (!empty($c['eTicketNumber'])) {
                $eticketMap[$c['ItemRPH'] ?? ''] = $c['eTicketNumber'];
            }
        }
    }
@endphp

<div x-data="{ open: true }">
    {{-- Leg Header --}}
    <div class="leg-header" @click="open = !open">
        <div>
            <div class="leg-route">
                {{ $legFirst['from'] ?? '' }}
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="color:var(--gray-300)"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                {{ $legLast['to'] ?? '' }}
                <span class="leg-badge {{ $legBadgeClass ?? 'outbound' }}">{{ $legLabel ?? 'Outbound' }}</span>
            </div>
            <div class="leg-meta">
                @if(!empty($legDate)) <span style="font-size:12px;color:var(--gray-500);">{{ $legDate }}</span> @endif
                <span class="leg-badge {{ ($legStops ?? 0) === 0 ? 'nonstop' : 'stop' }}">
                    {{ ($legStops ?? 0) === 0 ? 'Non-stop' : ($legStops . ' stop' . ($legStops > 1 ? 's' : '')) }}
                </span>
                @if(!empty($legDuration)) <span class="leg-dur">· {{ $legDuration }}</span> @endif
            </div>
        </div>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" :style="open ? 'transform:rotate(180deg);transition:.2s' : 'transition:.2s'" style="color:var(--gray-400);flex-shrink:0;"><polyline points="6 9 12 15 18 9"/></svg>
    </div>

    {{-- Segments --}}
    <div x-show="open" x-transition>
        @foreach($legSegs as $si => $seg)
            @php
                $flightKey = ($seg['airlineCode'] ?? '') . ltrim($seg['flightNo'] ?? '', $seg['airlineCode'] ?? '');
                $flightKey = $seg['flightNo'] ?? '';
                $segPnr    = $pnrMap[$flightKey] ?? '';
                $equip     = $seg['equipment'] ?? '';
                $equipLbl  = $equipMap[$equip]  ?? $equip;
                $bagArr    = array_filter((array)($breakdown[0]['baggage']      ?? []), fn($v) => $v !== '');
                $cabArr    = array_filter((array)($breakdown[0]['cabinBaggage'] ?? []), fn($v) => $v !== '');
                $bagStr    = implode(' / ', array_unique($bagArr)) ?: '1 × 23 kg';
                $cabinBag  = implode(' / ', array_unique($cabArr)) ?: '7 kg';
                $depDt     = !empty($seg['departDT']) ? \Carbon\Carbon::parse($seg['departDT']) : null;
                $arrDt     = !empty($seg['arriveDT']) ? \Carbon\Carbon::parse($seg['arriveDT']) : null;
                $crossDay  = $depDt && $arrDt && $arrDt->format('Ymd') !== $depDt->format('Ymd');
            @endphp

            {{-- Layover between segments --}}
            @if($si > 0 && !empty($legLayovers[$si-1]))
            <div style="padding: 0 20px;">
                <div class="layover">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    Layover in {{ $legSegs[$si-1]['toCity'] ?? $legSegs[$si-1]['to'] ?? '' }} · {{ $legLayovers[$si-1] }}
                </div>
            </div>
            @endif

            <div class="seg-wrap">
                {{-- Airline bar --}}
                <div class="seg-airline-row">
                    <div class="seg-airline-left">
                        @if(!empty($seg['airlineLogo']))
                            <img class="seg-logo" src="{{ $seg['airlineLogo'] }}" alt="{{ $seg['airline'] ?? '' }}">
                        @endif
                        <div>
                            <div class="seg-airline-name">{{ $seg['airline'] ?? '' }}</div>
                            <div class="seg-meta">
                                {{ $seg['flightNo'] ?? '' }}
                                @if($equipLbl) · {{ $equipLbl }} @endif
                                @if(!empty($seg['cabin'])) · {{ $seg['cabin'] }} @endif
                                @if(!empty($seg['resBookCode'])) · Class {{ $seg['resBookCode'] }} @endif
                            </div>
                        </div>
                    </div>
                    <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                        @if($segPnr)
                            <span class="seg-pnr">PNR: {{ $segPnr }}</span>
                        @endif
                        @if(!empty($seg['isCodeshare']) && $seg['isCodeshare'])
                            <span style="font-size:10.5px;color:var(--gray-400);padding:2px 7px;border-radius:999px;background:var(--gray-100);">Operated by {{ $seg['operatingAirline'] ?? $seg['operatingCode'] }}</span>
                        @endif
                    </div>
                </div>

                {{-- Timeline --}}
                <div class="seg-timeline">
                    <div class="seg-spine">
                        <div class="seg-dot dep"></div>
                        <div class="seg-line"></div>
                        <div class="seg-dot arr"></div>
                    </div>
                    <div class="seg-stops" style="width:100%">
                        {{-- Departure --}}
                        <div class="seg-stop">
                            <div>
                                <div class="seg-time">{{ $seg['departTime'] ?? '' }}</div>
                                @if($depDt)<div class="seg-date">{{ $depDt->format('D, d M') }}</div>@endif
                            </div>
                            <div>
                                <div class="seg-place">{{ $seg['from'] ?? '' }}</div>
                                <div class="seg-city">{{ $seg['fromCity'] ?? $seg['from'] ?? '' }}</div>
                                <div class="seg-terminal">{{ $seg['fromAirport'] ?? '' }}</div>
                            </div>
                            <div class="seg-baggage">
                                <span class="seg-bag-tag"><span class="seg-bag-icon checked" aria-hidden="true"></span>{{ $bagStr }}</span>
                                <span class="seg-bag-tag" style="background:var(--blue-lt);color:var(--blue);"><span class="seg-bag-icon cabin" aria-hidden="true"></span>{{ $cabinBag }}</span>
                            </div>
                        </div>

                        {{-- Duration --}}
                        <div class="seg-dur-row">
                            <span class="seg-dur">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                {{ floor($seg['duration']/60) }}h {{ $seg['duration']%60 }}m flight
                            </span>
                        </div>

                        {{-- Arrival --}}
                        <div class="seg-stop" style="padding-bottom:0">
                            <div>
                                <div class="seg-time">{{ $seg['arriveTime'] ?? '' }}</div>
                                @if($arrDt)<div class="seg-date" style="{{ $crossDay ? 'color:var(--amber);font-weight:700;' : '' }}">{{ $arrDt->format('D, d M') }}@if($crossDay) <span style="font-size:10px;">(+1)</span>@endif</div>@endif
                            </div>
                            <div>
                                <div class="seg-place">{{ $seg['to'] ?? '' }}</div>
                                <div class="seg-city">{{ $seg['toCity'] ?? $seg['to'] ?? '' }}</div>
                                <div class="seg-terminal">{{ $seg['toAirport'] ?? '' }}</div>
                            </div>
                            <div></div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
