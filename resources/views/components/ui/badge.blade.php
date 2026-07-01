@props(['variant' => 'neutral'])

<span {{ $attributes->class('tw-ui-badge tw-ui-badge--'.$variant) }}>{{ $slot }}</span>
