@props(['name', 'size' => 20])
<svg {{ $attributes->class('tw-icon') }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($name)
        @case('arrow-left') <path d="M19 12H5m7 7-7-7 7-7"/> @break
        @case('arrow-right') <path d="M5 12h14m-7-7 7 7-7 7"/> @break
        @case('cloud-check') <path d="M7 18h10a4 4 0 0 0 .7-7.94A6 6 0 0 0 6.2 8.3 4.5 4.5 0 0 0 7 18Z"/><path d="m9 13 2 2 4-4"/> @break
        @case('check-circle') <circle cx="12" cy="12" r="9"/><path d="m8 12 2.7 2.7L16.5 9"/> @break
        @case('upload') <path d="M12 16V4m-4 4 4-4"/><path d="M5 14v5h14v-5"/> @break
        @case('plane') <path d="M22 2 9.5 14.5 3 12l-2 2 7 3 3 7 2-2-2.5-6.5L23 3Z"/> @break
        @case('hotel') <path d="M4 21V3h11v18M8 7h3m-3 4h3m-3 4h3m4-7h5v13M2 21h20"/> @break
        @case('shield') <path d="M12 3 4 6v5c0 5 3.4 8.6 8 10 4.6-1.4 8-5 8-10V6Z"/><path d="m9 12 2 2 4-4"/> @break
        @case('lock') <rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/> @break
        @default <circle cx="12" cy="12" r="9"/><path d="M12 8v4m0 4h.01"/>
    @endswitch
</svg>
