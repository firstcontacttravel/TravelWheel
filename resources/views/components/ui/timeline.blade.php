@props(['items'])

<ol {{ $attributes->class('tw-ui-timeline') }}>
    @foreach($items as $index => $item)
        @php($state = $item['state'] ?? 'upcoming')
        <li class="tw-ui-timeline__item tw-ui-timeline__item--{{ $state }}" @if($state === 'current') aria-current="step" @endif>
            <span class="tw-ui-timeline__marker">{{ $state === 'complete' ? '✓' : $index + 1 }}</span>
            <div>
                <h3 class="tw-ui-timeline__title">{{ $item['title'] }}</h3>
                @if(!empty($item['meta']))<span class="tw-ui-timeline__meta">{{ $item['meta'] }}</span>@endif
                @if(!empty($item['copy']))<p class="tw-ui-timeline__copy">{{ $item['copy'] }}</p>@endif
            </div>
        </li>
    @endforeach
</ol>
