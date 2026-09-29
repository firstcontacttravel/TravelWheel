{{--
    Ticket status card.

    The three steps use the same dot language as the queue table and the hero,
    so a filled dot means "settled" everywhere in the panel rather than only
    here.

    @param string $headline
    @param array  $state   ['text' =>, 'tone' =>, 'shape' =>]
    @param list<array{label:string, value:string, done:bool}> $steps
    @param array<string,string> $metrics
--}}
<div>
    <div class="tc-bk-head" style="padding:0 0 var(--tc-space-4)">
        <div>
            <span class="tc-bk-port-kind">Ticket status</span>
            <span class="tc-bk-ref" style="font-size:var(--tc-text-display);font-family:var(--tc-font-sans)">{{ $headline }}</span>
        </div>
        <span class="tc-status tc-status-{{ $state['tone'] }} {{ $state['shape'] }}">{{ $state['text'] }}</span>
    </div>

    <div class="tc-feed">
        @foreach ($steps as $step)
            <div class="tc-feed-item" style="padding-block:var(--tc-space-2)">
                <span @class(['tc-status', 'tc-status-positive' => $step['done'], 'tc-status-idle tc-status-pending' => ! $step['done']]) aria-hidden="true"></span>
                <div class="tc-feed-head">
                    <span class="tc-feed-title">{{ $step['label'] }}</span>
                    <span class="tc-feed-when">{{ $step['value'] }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <dl class="tc-pax-body" style="padding:var(--tc-space-4) 0 0">
        @foreach ($metrics as $label => $value)
            <div>
                <dt>{{ $label }}</dt>
                <dd>{{ $value }}</dd>
            </div>
        @endforeach
    </dl>
</div>
