@props(['variant' => 'info', 'role' => null])

<div role="{{ $role ?? ($variant === 'error' ? 'alert' : 'status') }}" {{ $attributes->class('tw-ui-alert tw-ui-alert--'.$variant) }}>
    {{ $slot }}
</div>
