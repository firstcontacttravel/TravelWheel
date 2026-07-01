@props([
    'variant' => 'empty',
    'title',
    'copy' => null,
    'icon' => null,
])

<section {{ $attributes->class('tw-ui-state tw-ui-state--'.$variant) }} @if($variant === 'loading') aria-busy="true" aria-live="polite" @endif>
    <div class="tw-ui-state__icon" aria-hidden="true">
        @if($variant === 'loading')<span class="tw-ui-spinner"></span>@else{{ $icon ?? ($variant === 'error' ? '!' : '—') }}@endif
    </div>
    <h2 class="tw-ui-state__title">{{ $title }}</h2>
    @if($copy)<p class="tw-ui-state__copy">{{ $copy }}</p>@endif
    @isset($action)<div class="tw-ui-state__action">{{ $action }}</div>@endisset
</section>
