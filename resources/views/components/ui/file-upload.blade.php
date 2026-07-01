@props([
    'name',
    'id' => null,
    'accept' => '.pdf,.jpg,.jpeg,.png',
    'maxSize' => '10 MB',
    'invalid' => false,
    'multiple' => false,
])

@php($inputId = $id ?: $name)

<div>
    <label for="{{ $inputId }}" class="tw-ui-upload {{ $invalid ? 'tw-ui-upload--invalid' : '' }}">
        <input id="{{ $inputId }}" name="{{ $name }}{{ $multiple ? '[]' : '' }}" type="file" accept="{{ $accept }}" @if($multiple) multiple @endif {{ $attributes->class('tw-ui-upload__input')->merge(['aria-invalid' => $invalid ? 'true' : null]) }}>
        <span class="tw-ui-upload__content">
            <span class="tw-ui-upload__title">Choose a file or drop it here</span>
            <span class="tw-ui-upload__copy">Accepted: {{ $accept }} · Maximum {{ $maxSize }}</span>
        </span>
    </label>
    @isset($file)<div class="tw-ui-upload__file">{{ $file }}</div>@endisset
</div>
