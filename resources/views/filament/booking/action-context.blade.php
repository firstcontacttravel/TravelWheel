{{--
    The context block at the top of an action modal.

    Two tables built this identically and separately; one partial now. It
    answers "which record am I about to act on" before someone confirms a
    payment verification or a ticket order.

    @param string $kicker
    @param string $title
    @param string $subtitle
    @param array<string,string> $rows
--}}
<div class="tc-bk" style="border-radius:var(--tc-radius-sm)">
    <div class="tc-bk-head" style="padding:var(--tc-space-4)">
        <div>
            <span class="tc-bk-port-kind">{{ $kicker }}</span>
            <span class="tc-bk-ref" style="font-size:var(--tc-text-title)">{{ $title }}</span>
            <span class="tc-bk-sub">{{ $subtitle }}</span>
        </div>
    </div>

    <dl class="tc-pax-body" style="border-top:1px solid var(--tc-border-subtle)">
        @foreach ($rows as $label => $value)
            <div>
                <dt>{{ $label }}</dt>
                <dd @class(['tc-money' => str_contains(strtolower($label), 'amount') || str_contains(strtolower($label), 'charge') || str_contains(strtolower($label), 'total')])>{{ $value }}</dd>
            </div>
        @endforeach
    </dl>
</div>
