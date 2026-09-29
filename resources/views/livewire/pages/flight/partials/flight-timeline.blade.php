{{--
    One leg of an itinerary, drawn as a vertical timeline: a hairline spine
    with a node at every airport, and the operating carrier plus that flight's
    allowances sitting between them. A connection reads as one continuous
    journey instead of three separate bordered boxes.

    Included once per leg — outbound, inbound, and each multi-city leg — so all
    three render identically. The three hand-copied versions this replaces had
    already drifted: the inbound one silently dropped the cabin-bag and
    booking-class rows that the other two showed.

    @param string $segments  Alpine expression for the segment array
    @param string $layovers  Alpine expression for that leg's layover-duration array
    @param string $key       x-for key prefix, unique per leg within a card
    @param string $legIndex  Alpine expression for the leg index, for baggage lookup
--}}
<div class="sr-timeline">
    <template x-for="(seg, si) in {{ $segments }}" :key="'{{ $key }}'+si">
        <div>

            {{-- Layover sits on the spine between the previous arrival and this departure --}}
            <template x-if="si > 0">
                <div class="sr-detail-layover">
                    <span class="sr-ic sr-ic-sm sr-ic-clock" aria-hidden="true"></span>
                    <span x-text="({{ $layovers }}?.[si-1] ? {{ $layovers }}[si-1] + ' layover in ' : 'Layover in ') + ({{ $segments }}[si-1]?.toCity || '')"></span>
                </div>
            </template>

            {{-- Departure --}}
            <div class="sr-tl-node" :class="si > 0 ? 'mid' : ''">
                <div class="sr-tl-row">
                    <span class="sr-tl-time" x-text="_time(seg.departTime)"></span>
                    <span class="sr-tl-station">
                        <span class="sr-tl-place" x-text="seg.fromCity"></span>
                        <span class="sr-tl-airport" x-text="seg.fromAirport"></span>
                    </span>
                </div>
            </div>

            {{-- The flight itself --}}
            <div class="sr-tl-flight">
                <div class="sr-tl-carrier">
                    <template x-if="seg.airlineLogo">
                        <img :src="seg.airlineLogo" :alt="seg.airline">
                    </template>
                    <span x-text="seg.airline"></span>
                    <span class="sr-tl-carrier-code" x-text="seg.flightNo"></span>
                    <span class="sr-tl-carrier-dur" x-text="Math.floor(seg.duration/60) + 'h ' + (seg.duration%60) + 'm'"></span>
                </div>
                <div class="sr-tl-facts">
                    <span class="sr-fact">
                        <span class="sr-ic sr-ic-sm sr-ic-luggage" aria-hidden="true"></span>
                        <strong x-text="_luggageLabel(flight, seg, {{ $legIndex }})"></strong> checked
                    </span>
                    <span class="sr-fact">
                        <span class="sr-ic sr-ic-sm sr-ic-cabin" aria-hidden="true"></span>
                        <strong x-text="_cabinBagLabel(flight, seg, {{ $legIndex }})"></strong> cabin
                    </span>
                    <span class="sr-fact" x-show="seg.equipment">
                        <span class="sr-ic sr-ic-sm sr-ic-aircraft" aria-hidden="true"></span>
                        <span x-text="seg.equipment"></span>
                    </span>
                    <span class="sr-fact" x-show="seg.resBookCode">
                        <span class="sr-ic sr-ic-sm sr-ic-ticket" aria-hidden="true"></span>
                        Class <strong x-text="seg.resBookCode"></strong>
                    </span>
                    <span class="sr-fact"
                          :class="{ low: seg.seatsLeft !== null && seg.seatsLeft !== undefined && seg.seatsLeft <= 5 }"
                          x-show="seg.seatsLeft !== null && seg.seatsLeft !== undefined && seg.seatsLeft !== ''">
                        <span class="sr-ic sr-ic-sm sr-ic-seat" aria-hidden="true"></span>
                        <strong x-text="seg.seatsLeft"></strong> seats left
                    </span>
                </div>
            </div>

            {{-- Arrival --}}
            <div class="sr-tl-node" :class="si < {{ $segments }}.length - 1 ? 'mid' : ''">
                <div class="sr-tl-row">
                    <span class="sr-tl-time" x-text="_time(seg.arriveTime)"></span>
                    <span class="sr-tl-station">
                        <span class="sr-tl-place" x-text="seg.toCity"></span>
                        <span class="sr-tl-airport" x-text="seg.toAirport"></span>
                    </span>
                </div>
            </div>

        </div>
    </template>
</div>
