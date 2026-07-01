@props(['invalid' => false])

<textarea {{ $attributes->class('tw-ui-control')->merge(['aria-invalid' => $invalid ? 'true' : null]) }}>{{ $slot }}</textarea>
