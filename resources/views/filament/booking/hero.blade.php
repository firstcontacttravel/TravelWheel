{{--
    Booking workspace hero.

    Replaces ~70 lines of PHP string concatenation. The markup is here so the
    console's own primitives can reach it — the same drawn route connector as
    the queue table, the same status dots, the same mono for anything read
    aloud down a phone line.

    @param string      $ref
    @param list<string> $meta      trip type, UniqueID, fare type
    @param array        $states    label => ['text' => , 'tone' => , 'shape' => ]
    @param array        $from      ['code' =>, 'city' =>]
    @param array        $to
    @param string|null  $note      stop count / trip label under the connector
    @param array        $facts     label => value
    @param list<array>  $steps     ['label' =>, 'value' =>, 'done' => bool]
    @param list<array>  $legs      ['kind' =>, 'route' =>, 'meta' => list<string>]
--}}
<div class="tc-bk">
    <div class="tc-bk-head">
        <div>
            <span class="tc-bk-ref">{{ $ref }}</span>
            <span class="tc-bk-sub">
                @foreach ($meta as $item)
                    @if (! $loop->first)<span aria-hidden="true">·</span>@endif
                    <span @class(['tc-mono' => $loop->index > 0])>{{ $item }}</span>
                @endforeach
            </span>
        </div>

        <dl class="tc-bk-states">
            @foreach ($states as $label => $state)
                <div class="tc-bk-state">
                    <dt>{{ $label }}</dt>
                    <dd>
                        <span class="tc-status tc-status-{{ $state['tone'] }} {{ $state['shape'] }}">{{ $state['text'] }}</span>
                    </dd>
                </div>
            @endforeach
        </dl>
    </div>

    <div class="tc-bk-route">
        <div class="tc-bk-port">
            <span class="tc-bk-port-kind">From</span>
            <span class="tc-bk-port-code">{{ $from['code'] }}</span>
            @if (filled($from['city']))
                <span class="tc-bk-port-city">{{ $from['city'] }}</span>
            @endif
        </div>

        <div class="tc-bk-link" aria-hidden="true">
            <span class="tc-bk-link-line"></span>
            @if (filled($note))
                <span class="tc-bk-link-note">{{ $note }}</span>
            @endif
        </div>

        <div class="tc-bk-port is-to">
            <span class="tc-bk-port-kind">To</span>
            <span class="tc-bk-port-code">{{ $to['code'] }}</span>
            @if (filled($to['city']))
                <span class="tc-bk-port-city">{{ $to['city'] }}</span>
            @endif
        </div>
    </div>

    <dl class="tc-bk-facts">
        @foreach ($facts as $label => $value)
            <div class="tc-bk-fact">
                <dt>{{ $label }}</dt>
                <dd @class(['tc-money' => str_contains(strtolower($label), 'total') || str_contains(strtolower($label), 'charge')])>{{ $value }}</dd>
            </div>
        @endforeach
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

    @if ($legs !== [])
        <div class="tc-bk-legs">
            @foreach ($legs as $leg)
                <div class="tc-bk-leg">
                    <span class="tc-bk-leg-kind">{{ $leg['kind'] }}</span>
                    <span class="tc-route tc-t-body">
                        @foreach ($leg['legs'] as $code)
                            @if (! $loop->first)<span class="tc-route-line"></span>@endif
                            <span class="tc-mono @unless ($loop->first || $loop->last) tc-route-via @endunless">{{ $code }}</span>
                        @endforeach
                    </span>
                    <span class="tc-bk-leg-meta">
                        @foreach ($leg['meta'] as $item)
                            <span>{{ $item }}</span>
                        @endforeach
                    </span>
                </div>
            @endforeach
        </div>
    @endif
</div>
