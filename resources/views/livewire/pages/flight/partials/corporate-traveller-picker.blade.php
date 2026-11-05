{{-- Choose a saved traveller, for a signed-in company. Expects $i and $pax. --}}
@if ($this->isCompanyBooking())
    <div class="bk-fieldset">
        <div class="bk-fieldset-head">
            <span class="bk-fieldset-title">Saved traveller</span>
            <span class="bk-fieldset-note">Optional. Fills in this traveller's details.</span>
        </div>
        <div class="bk-field">
            <label class="bk-label" for="saved-traveller-{{ $i }}">Choose someone your company has saved</label>
            <select class="bk-select" id="saved-traveller-{{ $i }}" wire:change="useSavedTraveller({{ $i }}, $event.target.value)">
                <option value="">Guest, or enter details below</option>
                @foreach ($this->savedTravellers() as $option)
                    <option value="{{ $option['id'] }}" @selected((int) ($pax['saved_traveller_id'] ?? 0) === $option['id'])>{{ $option['label'] }}</option>
                @endforeach
            </select>
        </div>

        @if (filled($pax['saved_traveller_id'] ?? null))
            @if (blank($pax['passport_masked'] ?? null))
                <p class="bk-fieldset-note" style="margin:8px 0 0;">No passport is saved for this traveller. Enter it below if the trip needs one.</p>
            @endif

            @if ($warning = $this->passportWarningFor($pax['saved_traveller_id']))
                <p role="alert" style="margin:8px 0 0;padding:8px 12px;border-radius:8px;background:#fffaf0;color:#92400e;font-size:13px;">{{ $warning }}</p>
            @endif
        @else
            <label style="display:flex;align-items:center;gap:8px;margin:10px 0 0;font-size:13px;">
                <input type="checkbox" wire:model="passengers.{{ $i }}.save_traveller">
                Save this traveller for next time
            </label>
        @endif
    </div>
@endif
