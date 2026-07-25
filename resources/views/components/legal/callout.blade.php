@props(['variant' => 'info'])

@php
    $icons = [
        'info' => 'info',
        'warning' => 'warning',
        'danger' => 'x-circle',
        'success' => 'check-circle',
    ];
    $icon = $icons[$variant] ?? $icons['info'];
@endphp

<div class="legal-callout legal-callout--{{ $variant }}" role="{{ $variant === 'warning' || $variant === 'danger' ? 'alert' : 'note' }}">
    <span class="legal-callout__icon" aria-hidden="true">
        <i class="ph-fill ph-{{ $icon }}"></i>
    </span>
    <div class="legal-callout__body">{{ $slot }}</div>
</div>
