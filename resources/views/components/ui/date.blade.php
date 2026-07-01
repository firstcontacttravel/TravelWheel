@props(['invalid' => false])

<input type="date" {{ $attributes->class('tw-ui-control')->merge(['aria-invalid' => $invalid ? 'true' : null]) }}>
