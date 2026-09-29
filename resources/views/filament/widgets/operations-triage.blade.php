@php
    $broken = $this->getBroken();
    $waiting = $this->getWaiting();
    $money = $this->getMoney();
@endphp

<div class="tc-triage">
    {{--
        BROKEN — first, loud, and with a reason. When there is nothing wrong
        this whole band is one calm line: a dashboard that is usually quiet is
        one people keep looking at, and a dashboard that is permanently amber
        is one they stop seeing.
    --}}
    <section class="tc-triage-block" @class(['is-clear' => $broken === []])>
        <header class="tc-triage-head">
            <h2 class="tc-t-label">Needs attention</h2>
            <span class="tc-t-micro">Checked {{ $this->getCheckedAt() }} WAT</span>
        </header>

        @if ($broken === [])
            <p class="tc-triage-clear tc-t-body">
                <span class="tc-status tc-status-positive"></span>
                Nothing needs attention.
            </p>
        @else
            <ul class="tc-triage-alerts">
                @foreach ($broken as $signal)
                    <li>
                        @if ($signal['url'])
                            <a href="{{ $signal['url'] }}" class="tc-alert">
                        @else
                            <div class="tc-alert">
                        @endif
                            @if ($signal['count'] !== null)
                                <span class="tc-alert-count tc-num">{{ $signal['count'] }}</span>
                            @else
                                <span class="tc-alert-count tc-alert-count-mark" aria-hidden="true">!</span>
                            @endif
                            <span class="tc-alert-body">
                                <span class="tc-alert-label">{{ $signal['label'] }}</span>
                                <span class="tc-alert-detail">{{ $signal['detail'] }}</span>
                            </span>
                            @if ($signal['url'])
                                <span class="tc-alert-go" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7"/>
                                    </svg>
                                </span>
                            @endif
                        @if ($signal['url'])
                            </a>
                        @else
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- WAITING — normal queues. Counts, no alarm colour. --}}
    <section class="tc-triage-block">
        <header class="tc-triage-head">
            <h2 class="tc-t-label">Waiting</h2>
        </header>

        <ul class="tc-queues">
            @foreach ($waiting as $queue)
                <li>
                    @if ($queue['url'])
                        <a href="{{ $queue['url'] }}" class="tc-queue">
                    @else
                        <div class="tc-queue">
                    @endif
                        <span @class(['tc-queue-count', 'tc-num', 'is-zero' => $queue['count'] === 0])>{{ $queue['count'] }}</span>
                        <span class="tc-queue-label">{{ $queue['label'] }}</span>
                    @if ($queue['url'])
                        </a>
                    @else
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    </section>

    {{-- MONEY — last, because it is context rather than an action. It used to
         be the loudest thing on the screen. --}}
    <section class="tc-triage-block">
        <header class="tc-triage-head">
            <h2 class="tc-t-label">Money</h2>
            <span class="tc-t-micro">Last 30 days</span>
        </header>

        <dl class="tc-money-row">
            <div>
                <dt class="tc-t-micro">Paid revenue</dt>
                <dd class="tc-money tc-t-display">{{ $money['revenue'] }}</dd>
            </div>
            <div>
                <dt class="tc-t-micro">Service charges</dt>
                <dd class="tc-money tc-t-display">{{ $money['charges'] }}</dd>
            </div>
            <div>
                <dt class="tc-t-micro">Ticketed</dt>
                <dd class="tc-num tc-t-display">{{ $money['ticketed'] }}</dd>
            </div>
            <div>
                <dt class="tc-t-micro">Booked today</dt>
                <dd class="tc-num tc-t-display">{{ $money['today'] }}</dd>
            </div>
        </dl>
    </section>
</div>
