{{-- Expects $name and $label; optional $type, $value, $help, $autocomplete, $required, $inputmode. --}}
<div class="biz-field">
    <label for="{{ $name }}">{{ $label }}</label>
    <input
        class="biz-input"
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type ?? 'text' }}"
        @if (($type ?? 'text') !== 'password') value="{{ $value ?? old($name) }}" @endif
        @if ($autocomplete ?? false) autocomplete="{{ $autocomplete }}" @endif
        @if ($inputmode ?? false) inputmode="{{ $inputmode }}" @endif
        @if ($required ?? true) required @endif
        @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
    >
    @if ($help ?? false)
        <div class="biz-help">{{ $help }}</div>
    @endif
    @error($name)
        <div class="biz-error" id="{{ $name }}-error">{{ $message }}</div>
    @enderror
</div>
