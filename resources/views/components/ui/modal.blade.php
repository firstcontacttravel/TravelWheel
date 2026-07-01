@props(['id', 'title'])

<dialog id="{{ $id }}" {{ $attributes->class('tw-ui-modal') }} aria-labelledby="{{ $id }}-title">
    <header class="tw-ui-modal__header">
        <h2 id="{{ $id }}-title" class="tw-ui-modal__title">{{ $title }}</h2>
        <button type="button" class="tw-ui-modal__close" aria-label="Close" onclick="this.closest('dialog').close()">&times;</button>
    </header>
    <div class="tw-ui-modal__body">{{ $slot }}</div>
    @isset($footer)<footer class="tw-ui-modal__footer">{{ $footer }}</footer>@endisset
</dialog>
