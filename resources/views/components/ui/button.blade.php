@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'disabled' => false,
])

@php
    $classes = 'tw-ui-button tw-ui-button--'.$variant.($size !== 'md' ? ' tw-ui-button--'.$size : '');
@endphp

@if ($href)
    <a href="{{ $disabled ? null : $href }}"
       @if($disabled) aria-disabled="true" tabindex="-1" @endif
       {{ $attributes->class($classes) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" @disabled($disabled) {{ $attributes->class($classes) }}>
        {{ $slot }}
    </button>
@endif
