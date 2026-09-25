{{--
    TravelFlex application hero.

    The last screen still rendering from string concatenation, and the reason
    tw-booking-* / tw-timeline-* / tw-status-pill survived Phase 6. Same parts
    as the flight booking workspace, so the two look like one product.

    @param string $ref
    @param string $applicant
    @param list<string> $meta
    @param string $amount
    @param array $states
    @param list<array{label:string,value:string,done:bool}> $steps
--}}
<div class="tc-bk">
    <div class="tc-bk-head">
        <div>
            <span class="tc-bk-port-kind">TravelFlex application</span>
            <span class="tc-bk-ref">{{ $ref }}</span>
            <span class="tc-bk-sub">
                <span>{{ $applicant }}</span>
                @foreach ($meta as $item)
                    <span aria-hidden="true">·</span><span>{{ $item }}</span>
                @endforeach
            </span>
        </div>

        <dl class="tc-bk-states">
            @foreach ($states as $label => $state)
                <div class="tc-bk-state">
                    <dt>{{ $label }}</dt>
                    <dd><span class="tc-status tc-status-{{ $state['tone'] }} {{ $state['shape'] }}">{{ $state['text'] }}</span></dd>
                </div>
            @endforeach
        </dl>
    </div>

    <dl class="tc-bk-facts" style="border-top:1px solid var(--tc-border-subtle)">
        <div class="tc-bk-fact">
            <dt>Grand total</dt>
            <dd class="tc-money">{{ $amount }}</dd>
        </div>
    </dl>

    <div class="tc-bk-progress">
        @foreach ($steps as $step)
            <div @class(['tc-bk-step', 'is-pending' => ! $step['done']])>
                <span class="tc-bk-step-label">
                    <span @class(['tc-status', 'tc-status-positive' => $step['done'], 'tc-status-idle tc-status-pending' => ! $step['done']])></span>
                    {{ $step['label'] }}
                </span>
                <span class="tc-bk-step-value">{{ $step['value'] }}</span>
            </div>
        @endforeach
    </div>
</div>
