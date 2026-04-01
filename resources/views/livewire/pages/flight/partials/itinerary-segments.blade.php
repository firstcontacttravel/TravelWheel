{{--
    resources/views/livewire/pages/flight/partials/itinerary-segments.blade.php
    Reusable partial: renders one leg's segments with layovers.
    Variables expected from parent:
      $legSegments       – array of segment maps
      $layoverDurations  – array of layover strings (parallel to gaps between segments)
      $legLabel          – string label e.g. "Outbound", "Return", "Leg 2"
      $stopCount         – int
      $durationLabel     – string e.g. "2h 15m"
      $dateLabel         – string e.g. "Mon, 10 Apr"
      $breakdown         – fare breakdown array (for baggage)
      $cabin             – string e.g. "Economy"
      $equipMap          – array code → name
      $isOutbound        – bool (controls badge colour)
--}}

@php
    $firstSeg = $legSegments[0] ?? [];
    $lastSeg  = !empty($legSegments) ? $legSegments[count($legSegments)-1] : [];
@endphp

<div class="bk-itin-leg" x-data="{ legOpen: true }">
    <div class="bk-itin-leg-head" @click="legOpen = !legOpen">
        <div>
            <div class="bk-itin-leg-route">
                {{ $firstSeg['from'] ?? '' }}
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                {{ $lastSeg['to'] ?? '' }}
                <span class="bk-outbound-badge">{{ $legLabel }}</span>
            </div>
            <div class="bk-itin-leg-meta">
                @if($dateLabel) <span>{{ $dateLabel }}</span> @endif
                <span class="bk-itin-leg-badge {{ $stopCount === 0 ? 'direct' : '' }}">
                    {{ $stopCount === 0 ? 'Non stop' : $stopCount . ' stop' . ($stopCount > 1 ? 's' : '') }}
                </span>
                @if($durationLabel) <span>· {{ $durationLabel }}</span> @endif
            </div>
        </div>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" :style="legOpen ? 'transform:rotate(180deg)' : ''"><polyline points="6 9 12 15 18 9"/></svg>
    </div>

    <div x-show="legOpen" x-transition>
        <div class="bk-itin-leg-body">
            @foreach($legSegments as $si => $seg)
                @php
                    $equip    = $seg['equipment'] ?? '';
                    $equipLbl = $equipMap[$equip] ?? $equip;
                    $bagArr   = (array)($breakdown[0]['baggage']      ?? []);
                    $cabArr   = (array)($breakdown[0]['cabinBaggage'] ?? []);
                    $bagStr   = implode(' / ', array_unique(array_filter($bagArr, fn($v) => $v !== ''))) ?: '1 × 23kg';
                    $cabinBag = implode(' / ', array_unique(array_filter($cabArr, fn($v) => $v !== ''))) ?: '1 × 7kg';
                @endphp

                @if($si > 0 && !empty($layoverDurations[$si - 1]))
                    <div class="bk-layover-strip">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        Layover in {{ $legSegments[$si-1]['toCity'] ?? $legSegments[$si-1]['to'] ?? '' }}
                        · {{ $layoverDurations[$si - 1] }}
                    </div>
                @endif

                <div class="bk-seg-group">
                    <div class="bk-seg-airline-bar">
                        <div class="bk-seg-airline-left">
                            @if(!empty($seg['airlineLogo']))
                                <img class="bk-seg-airline-logo" src="{{ $seg['airlineLogo'] }}" alt="{{ $seg['airline'] ?? '' }}">
                            @endif
                            <span class="bk-seg-airline-name">{{ $seg['airline'] ?? '' }}</span>
                            @if($equipLbl) <span style="color:var(--gray-300)">·</span> <span style="font-size:11px;color:var(--gray-400);">{{ $equipLbl }}</span> @endif
                            @if(!empty($seg['flightNo'])) <span style="color:var(--gray-300)">·</span> <span style="font-size:11px;color:var(--gray-400);">{{ $seg['flightNo'] }}</span> @endif
                            @if(!empty($seg['AirlinePNR'])) <span style="color:var(--gray-300)">·</span> <span style="font-size:11px;color:var(--green);font-weight:700;">PNR: {{ $seg['AirlinePNR'] }}</span> @endif
                        </div>
                        <div>
                            <span class="bk-seg-cabin-tag">{{ $seg['cabin'] ?? $cabin }}</span>
                            @if(!empty($seg['resBookCode'])) <span class="bk-seg-cabin-tag" style="margin-left:5px;">Class {{ $seg['resBookCode'] }}</span> @endif
                        </div>
                    </div>

                    <div class="bk-seg-timeline">
                        <div class="bk-seg-spine">
                            <div class="bk-seg-dot"></div>
                            <div class="bk-seg-line"></div>
                            <div class="bk-seg-dot end"></div>
                        </div>
                        <div class="bk-seg-stops">
                            <div class="bk-seg-stop">
                                <div class="bk-seg-time">{{ $seg['departTime'] }}</div>
                                <div>
                                    <div class="bk-seg-place">{{ $seg['fromCity'] ?? $seg['from'] ?? '' }}</div>
                                    <div class="bk-seg-place-sub">{{ $seg['fromAirport'] ?? '' }}</div>
                                    @if(!empty($seg['departureTerminal']))<div class="bk-seg-place-sub">Terminal {{ $seg['departureTerminal'] }}</div>@endif
                                </div>
                                <div class="bk-seg-bags">
                                    <span class="bk-seg-bags-lbl">Check-In</span>
                                    <span class="bk-seg-bags-val">{{ $seg['Baggage'] ?? $bagStr }}</span>
                                    <span class="bk-seg-bags-lbl" style="margin-top:4px;">Cabin</span>
                                    <span class="bk-seg-bags-val">{{ $cabinBag }}</span>
                                </div>
                            </div>
                            <div class="bk-seg-stop" style="padding-bottom:0;">
                                <div class="bk-seg-time" style="color:var(--gray-500);font-size:12px;">
                                    {{ floor($seg['duration']/60) }}h {{ $seg['duration']%60 }}m
                                </div>
                                <div></div><div></div>
                            </div>
                            <div class="bk-seg-stop" style="padding-top:4px;padding-bottom:0;">
                                <div class="bk-seg-time">{{ $seg['arriveTime'] }}</div>
                                <div>
                                    <div class="bk-seg-place">{{ $seg['toCity'] ?? $seg['to'] ?? '' }}</div>
                                    <div class="bk-seg-place-sub">{{ $seg['toAirport'] ?? '' }}</div>
                                    @if(!empty($seg['arrivalTerminal']))<div class="bk-seg-place-sub">Terminal {{ $seg['arrivalTerminal'] }}</div>@endif
                                </div>
                                <div></div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>