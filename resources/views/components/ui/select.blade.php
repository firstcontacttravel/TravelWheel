@props(['invalid' => false])

<div class="tw-ui-select-wrap">
    <select {{ $attributes->class('tw-ui-control')->merge(['aria-invalid' => $invalid ? 'true' : null]) }}>
        {{ $slot }}
    </select>
</div>
