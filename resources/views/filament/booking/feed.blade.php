{{--
    One history feed for three audit trails.

    Ticketing, payment verification and post-ticketing were three separate
    renderers producing three slightly different lists of the same shape, which
    is how they had drifted apart. One component now.

    @param list<array{title:string, when:string, body:string|null, tone:string, url?:string|null}> $items
    @param string $empty

    An item with a url (a Linear issue) links its title, opening in a new tab.
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
                        @if (filled($item['url'] ?? null))
                            <a class="tc-feed-title" href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer">{{ $item['title'] }}</a>
                        @else
                            <span class="tc-feed-title">{{ $item['title'] }}</span>
                        @endif
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
