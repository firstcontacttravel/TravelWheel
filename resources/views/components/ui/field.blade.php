@props([
    'label',
    'for' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
])

@php
    $descriptionId = $for ? $for.'-description' : null;
@endphp

<div {{ $attributes->class('tw-ui-field') }}>
    <label @if($for) for="{{ $for }}" @endif class="tw-ui-field__label">
        {{ $label }}
        @if($required)<span class="tw-ui-field__required" aria-hidden="true">*</span><span class="tw-ui-sr-only"> required</span>@endif
    </label>
    {{ $slot }}
    @if($error)
        <p @if($descriptionId) id="{{ $descriptionId }}" @endif class="tw-ui-field__error">{{ $error }}</p>
    @elseif($hint)
        <p @if($descriptionId) id="{{ $descriptionId }}" @endif class="tw-ui-field__hint">{{ $hint }}</p>
    @endif
</div>
