@props(['title' => null, 'description' => null])

<section {{ $attributes->class('tw-ui-card') }}>
    @if ($title || $description || isset($actions))
        <header class="tw-ui-card__header">
            <div>
                @if ($title)<h2 class="tw-ui-card__title">{{ $title }}</h2>@endif
                @if ($description)<p class="tw-ui-card__description">{{ $description }}</p>@endif
            </div>
            @isset($actions)<div>{{ $actions }}</div>@endisset
        </header>
    @endif

    <div class="tw-ui-card__body">{{ $slot }}</div>

    @isset($footer)<footer class="tw-ui-card__footer">{{ $footer }}</footer>@endisset
</section>
