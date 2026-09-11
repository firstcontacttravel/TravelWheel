{{--
    One flown segment on the booking page: who operates it, the two ends of
    it, and what you may carry.

    Included once per segment for the outbound, inbound and each multi-city
    leg. The three hand-copied versions this replaces had already drifted --
    and all three printed the checked-baggage figure twice, under "Baggage"
    and again under "Check In", so two labels showed one number.

    @param array  $seg       mapped segment
    @param string $bagStr    checked baggage allowance
    @param string $cabinBag  cabin baggage allowance
    @param string $equipLbl  aircraft name, may be blank
    @param string $cabin     itinerary cabin, used when the segment omits one
    @param string $layover   layover caption to show before this segment, may be blank
--}}
@if(!empty($layover))
    <div class="bk-layover-strip">
        <span class="bk-mini-icon bk-icon-clock" aria-hidden="true"></span>
        {{ $layover }}
    </div>
@endif

<div class="bk-seg-group">
    <div class="bk-seg-airline-bar">
        <div class="bk-seg-airline-left">
            @if(!empty($seg['airlineLogo']))
                <img class="bk-seg-airline-logo" src="{{ $seg['airlineLogo'] }}" alt="{{ $seg['airline'] ?? '' }}">
            @endif
            <span class="bk-seg-airline-name">{{ $seg['airline'] ?? '' }}</span>
            @if(!empty($seg['flightNo']))
                <span class="bk-seg-airline-meta">{{ $seg['flightNo'] }}</span>
            @endif
            @if(!empty($equipLbl))
                <span class="bk-seg-airline-meta">{{ $equipLbl }}</span>
            @endif
        </div>
        <span class="bk-seg-cabin-tag">
            {{ $seg['cabin'] ?? $cabin }}@if(!empty($seg['resBookCode'])) · {{ $seg['resBookCode'] }}@endif
        </span>
    </div>

    <div class="bk-seg-body">
        <div class="bk-seg-timeline">
            <div class="bk-seg-stop">
                <span class="bk-seg-time">{{ $bkTime($seg['departTime'] ?? '') }}</span>
                <span class="bk-seg-place-wrap">
                    <span class="bk-seg-place">{{ $seg['fromCity'] ?? $seg['from'] ?? '' }}</span>
                    <span class="bk-seg-place-sub">{{ $seg['fromAirport'] ?? '' }}</span>
                </span>
            </div>

            <div class="bk-seg-duration">
                <span>{{ floor(($seg['duration'] ?? 0) / 60) }}h {{ ($seg['duration'] ?? 0) % 60 }}m</span>
            </div>

            <div class="bk-seg-stop arrive">
                <span class="bk-seg-time">{{ $bkTime($seg['arriveTime'] ?? '') }}</span>
                <span class="bk-seg-place-wrap">
                    <span class="bk-seg-place">{{ $seg['toCity'] ?? $seg['to'] ?? '' }}</span>
                    <span class="bk-seg-place-sub">{{ $seg['toAirport'] ?? '' }}</span>
                </span>
            </div>
        </div>

        <div class="bk-seg-allow">
            <span class="bk-allow-chip">
                <span class="bk-mini-icon bk-icon-bag" aria-hidden="true"></span>
                <strong>{{ $bagStr }}</strong> checked
            </span>
            <span class="bk-allow-chip">
                <span class="bk-mini-icon bk-icon-cabin" aria-hidden="true"></span>
                <strong>{{ $cabinBag }}</strong> cabin
            </span>
        </div>
    </div>
</div>
