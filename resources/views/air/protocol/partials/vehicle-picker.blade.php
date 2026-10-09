{{--
    Vehicle choice for a pick-up / drop-off optional request: a dropdown whose
    options show each vehicle's picture, price and seats. Shown by the form's
    script when the matching "optinal_request{suffix}" select asks for a
    pick-up or drop-off; it sits in its own column beside that select. Expects $suffix (A, D, A2 or D2).
--}}
@once
    <style>
        .protocol-vehicles { position: relative; }
        .protocol-vehicle-toggle { width: 100%; display: flex; align-items: center; gap: 12px; min-height: 48px; padding: 7px 12px; border: 1px solid #ced4da; border-radius: 8px; background: #fff; text-align: left; color: #14201b; cursor: pointer; transition: border-color .15s, box-shadow .15s; }
        .protocol-vehicle-toggle:hover { border-color: #9ccfb4; }
        .protocol-vehicle-toggle:focus-visible, [data-vehicle-picker].is-open .protocol-vehicle-toggle { outline: none; border-color: #0d9c53; box-shadow: 0 0 0 3px rgba(13, 156, 83, .15); }
        [data-vehicle-picker].is-invalid .protocol-vehicle-toggle { border-color: #dc3545; }
        .protocol-vehicle-toggle .placeholder-text { flex: 1; color: #6c757d; }
        .protocol-vehicle-toggle .chosen { flex: 1; display: flex; align-items: center; gap: 12px; min-width: 0; }
        .protocol-vehicle-toggle .chevron { flex-shrink: 0; color: #5d6b64; transition: transform .15s; }
        [data-vehicle-picker].is-open .chevron { transform: rotate(180deg); }
        .protocol-vehicle-menu { display: none; position: absolute; left: 0; right: 0; z-index: 30; margin-top: 4px; padding: 6px; background: #fff; border: 1px solid #dfe8e3; border-radius: 8px; box-shadow: 0 12px 28px rgba(16, 33, 25, .16); }
        [data-vehicle-picker].is-open .protocol-vehicle-menu { display: block; }
        .protocol-vehicle { display: flex; align-items: center; gap: 12px; border-radius: 6px; padding: 7px 8px; margin: 0; cursor: pointer; transition: background .12s; }
        .protocol-vehicle:hover { background: #f3f7f5; }
        .protocol-vehicle:has(input:checked) { background: #f0faf4; }
        /* The radio does the work; the row is the visible control */
        .protocol-vehicle input { position: absolute; opacity: 0; width: 1px; height: 1px; pointer-events: none; }
        .protocol-vehicle .tick { margin-left: auto; color: #0d9c53; visibility: hidden; }
        .protocol-vehicle:has(input:checked) .tick { visibility: visible; }
        .protocol-vehicle img, .protocol-vehicle-toggle img { width: 60px; height: 38px; object-fit: contain; flex-shrink: 0; }
        .protocol-vehicle-info { min-width: 0; line-height: 1.25; }
        .protocol-vehicle-info strong { display: block; font-size: .9rem; color: #14201b; }
        .protocol-vehicle-info small { color: #5d6b64; font-size: .78rem; white-space: nowrap; }
        .protocol-vehicle-meta { display: flex; flex-wrap: wrap; align-items: baseline; gap: 2px 10px; margin-top: 2px; }
        .protocol-vehicle-price { font-weight: 700; color: #0d1883; font-size: .88rem; white-space: nowrap; }
        .protocol-vehicles-note { margin-top: 8px; font-size: .76rem; color: #8a5a00; background: #fff8e6; border: 1px solid #f3dfa8; border-radius: 6px; padding: 6px 9px; }
        .protocol-vehicles-error { display: none; margin-top: 4px; font-size: .8rem; color: #dc3545; }
        [data-vehicle-picker].is-invalid .protocol-vehicles-error { display: block; }
    </style>
@endonce

<div class="col-sm-6 protocol-field protocol-hide" id="vehicles{{ $suffix }}" data-vehicle-picker="{{ $suffix }}">
    <label class="form-label">Choose a vehicle</label>
    <div class="protocol-vehicles">
        <button type="button" class="protocol-vehicle-toggle" aria-haspopup="listbox" aria-expanded="false" data-vehicle-toggle>
            <span class="placeholder-text" data-vehicle-placeholder>Select a vehicle</span>
            <span class="chosen d-none" data-vehicle-chosen></span>
            <x-ph-icon name="caret-down" class="chevron" />
        </button>

        <div class="protocol-vehicle-menu" role="listbox" data-vehicle-menu>
            @foreach (\App\Support\ProtocolVehicles::all() as $key => $vehicle)
                <label class="protocol-vehicle" role="option">
                    <input type="radio" name="optional_vehicle{{ $suffix }}" value="{{ $key }}">
                    <img src="{{ asset($vehicle['image']) }}" alt="{{ $vehicle['name'] }}">
                    <span class="protocol-vehicle-info" data-vehicle-summary>
                        <strong>{{ $vehicle['name'] }}</strong>
                        <span class="protocol-vehicle-meta">
                            <span class="protocol-vehicle-price">{{ \App\Support\ProtocolVehicles::priceLabel($vehicle['price']) }}</span>
                            <small><x-ph-icon name="users" /> Up to {{ $vehicle['seats'] }} seats</small>
                        </span>
                    </span>
                    <x-ph-icon name="check" class="tick" />
                </label>
            @endforeach
        </div>
    </div>

    <div class="protocol-vehicles-error">Please choose a vehicle.</div>
    <div class="protocol-vehicles-note">Vehicle price is not included in the protocol amount; it is paid separately.</div>
</div>
