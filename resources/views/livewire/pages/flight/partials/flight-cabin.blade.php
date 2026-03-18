{{-- resources/views/livewire/partials/flight-cabin.blade.php --}}

<div class="input-box">
    <label class="label-text">Cabin Type</label>
    <div class="form-group">
        <select name="flight_type" id="flight_type"
            class="flight_type form-select form-select-sm p-3"
            wire:model="flight_type">
            <option value="Y">Economy</option>
            <option value="S">Premium Economy</option>
            <option value="C">Business</option>
            <option value="F">First</option>
        </select>
    </div>
</div>