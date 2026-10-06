{{--
    The Work panel on a booking's page.

    @param \App\Models\WorkItem|null $item
    @param array<string,string> $facts
    @param list<array{title:string, when:string, body:string|null, tone:string}> $escalations
    @param list<array{title:string, when:string, body:string|null, tone:string}> $feed
--}}
@if (! $item)
    <p class="tc-empty">No work recorded for this booking yet. Claiming it or adding a note starts its history.</p>
@else
    <dl class="tc-pax-body">
        @foreach ($facts as $label => $value)
            <div>
                <dt>{{ $label }}</dt>
                <dd>{{ $value }}</dd>
            </div>
        @endforeach
    </dl>

    @if ($escalations !== [])
        <h3 class="tc-t-label">Open escalations</h3>
        @include('filament.booking.feed', ['items' => $escalations, 'empty' => ''])
    @endif

    <h3 class="tc-t-label">History</h3>
    @include('filament.booking.feed', ['items' => $feed, 'empty' => 'No history yet.'])
@endif
