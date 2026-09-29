{{--
    One history feed for three audit trails.

    Ticketing, payment verification and post-ticketing were three separate
    renderers producing three slightly different lists of the same shape, which
    is how they had drifted apart. One component now.

    @param list<array{title:string, when:string, body:string|null, tone:string}> $items
    @param string $empty
--}}
@if ($items === [])
    <p class="tc-empty">{{ $empty }}</p>
@else
    <div class="tc-feed">
        @foreach ($items as $item)
            <div class="tc-feed-item">
                <span class="tc-status tc-status-{{ $item['tone'] }}" aria-hidden="true"></span>
                <div>
                    <div class="tc-feed-head">
                        <span class="tc-feed-title">{{ $item['title'] }}</span>
                        <span class="tc-feed-when">{{ $item['when'] }}</span>
                    </div>
                    @if (filled($item['body']))
                        <p class="tc-feed-body">{{ $item['body'] }}</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif
