@props(['eyebrow' => null, 'title', 'copy' => null])

<div {{ $attributes->class('tw-ui-page') }}>
    <div class="tw-ui-container">
        <header class="tw-ui-page__header">
            @if($eyebrow)<p class="tw-ui-page__eyebrow">{{ $eyebrow }}</p>@endif
            <h1 class="tw-ui-page__title">{{ $title }}</h1>
            @if($copy)<p class="tw-ui-page__copy">{{ $copy }}</p>@endif
        </header>

        @isset($progress)<div style="margin-bottom: 24px">{{ $progress }}</div>@endisset

        <div class="tw-ui-layout">
            <main class="tw-ui-stack">{{ $slot }}</main>
            @isset($summary)<aside class="tw-ui-page__summary">{{ $summary }}</aside>@endisset
        </div>
    </div>
</div>
