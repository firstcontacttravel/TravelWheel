@props(['invalid' => false])

<input {{ $attributes->class('tw-ui-control')->merge(['aria-invalid' => $invalid ? 'true' : null]) }}>
