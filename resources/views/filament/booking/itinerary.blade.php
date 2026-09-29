{{--
    The booked journey, drawn as a spine with a node at every airport.

    The previous layout drew each segment as a separate bordered card with
    nothing between them, so on LHR-DEL-DXB a person saw "arrive DEL" and
    "depart DEL" as two unrelated flights. The connection time — the number
    people actually worry about — could not be expressed at all.

    @param array $carrier ['name' =>, 'codes' =>, 'logo' =>]
    @param array<string,string> $facts
    @param list<array> $groups
--}}
<div>
    <div class="tc-bk-head" style="padding-inline:0;padding-top:0">
        <div>
            <span class="tc-pax-name">{{ $carrier['name'] }}</span>
            @if (filled($carrier['codes']))
                <span class="tc-bk-sub"><span class="tc-mono">{{ $carrier['codes'] }}</span></span>
            @endif
        </div>
    </div>

    <dl class="tc-bk-facts" style="border-block:1px solid var(--tc-border-subtle)">
        @foreach ($facts as $label => $value)
            <div class="tc-bk-fact">
                <dt>{{ $label }}</dt>
                <dd @class(['tc-money' => str_contains(strtolower($label), 'total') || str_contains(strtolower($label), 'fare') || str_contains(strtolower($label), 'charge')])>{{ $value }}</dd>
            </div>
        @endforeach
    </dl>

    @forelse ($groups as $group)
        <section class="tc-seg-group" @style(['margin-top: var(--tc-space-5)' => $loop->first])>
            <header class="tc-seg-group-head">
                <span class="tc-bk-leg-kind">{{ $group['label'] }}</span>
                <span class="tc-bk-leg-meta">
                    @foreach ($group['meta'] as $item)
                        <span>{{ $item }}</span>
                    @endforeach
                </span>
            </header>

            @foreach ($group['segments'] as $segment)
                @if (filled($segment['layover']))
                    <p class="tc-seg-layover">{{ $segment['layover'] }}</p>
                @endif

                <div class="tc-seg">
                    <span class="tc-seg-rail">
                        <span class="tc-seg-dot is-end"></span>
                        <span class="tc-seg-line"></span>
                    </span>
                    <div class="tc-seg-body">
                        <span class="tc-seg-stop">
                            <span class="tc-seg-time">{{ $segment['depart']['time'] }}</span>
                            <span class="tc-seg-code">{{ $segment['depart']['code'] }}</span>
                            <span class="tc-seg-place">{{ $segment['depart']['place'] }}</span>
                        </span>

                        <div class="tc-seg-carrier">
                            <strong>{{ $segment['flight'] }}</strong>
                            @foreach ($segment['meta'] as $item)
                                <span>{{ $item }}</span>
                            @endforeach
                        </div>

                        <span class="tc-seg-stop">
                            <span class="tc-seg-time">{{ $segment['arrive']['time'] }}</span>
                            <span class="tc-seg-code">{{ $segment['arrive']['code'] }}</span>
                            <span class="tc-seg-place">{{ $segment['arrive']['place'] }}</span>
                        </span>
                    </div>
                </div>
            @endforeach
        </section>
    @empty
        <p class="tc-empty">No itinerary segments were stored for this booking.</p>
    @endforelse
</div>
