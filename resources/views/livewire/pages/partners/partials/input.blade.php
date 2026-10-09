{{-- One labelled text field. Expects $model, $label, $required, $type ('text', 'email', 'tel' or 'textarea') and $col. --}}
<div class="{{ $col }}">
    <label class="form-label" for="{{ $model }}">{{ $label }} @if ($required)<span class="vr-req">*</span>@endif</label>
    @if ($type === 'textarea')
        <textarea id="{{ $model }}" class="form-control @error($model) is-invalid @enderror" rows="3" wire:model="{{ $model }}"></textarea>
    @else
        <input id="{{ $model }}" type="{{ $type }}" class="form-control @error($model) is-invalid @enderror" wire:model="{{ $model }}">
    @endif
    @error($model) <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
