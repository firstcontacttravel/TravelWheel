{{--
    Vehicle choice for a pick-up / drop-off optional request. Shown by the
    form's script when the matching "optinal_request{suffix}" select asks for
    a pick-up or drop-off. Expects $suffix (A, D, A2 or D2).
--}}
@once
    <style>
        .protocol-vehicles { margin-top: 10px; }
        .protocol-vehicles-title { font-size: .82rem; font-weight: 700; color: #14201b; margin-bottom: 6px; }
        .protocol-vehicle { display: flex; align-items: center; gap: 12px; border: 1px solid #dfe8e3; border-radius: 8px; padding: 8px 10px; margin-bottom: 8px; cursor: pointer; background: #fff; transition: border-color .15s, background .15s; }
        .protocol-vehicle:hover { border-color: #9ccfb4; }
        .protocol-vehicle:has(input:checked) { border-color: #0d9c53; background: #f0faf4; box-shadow: 0 0 0 1px #0d9c53 inset; }
        .protocol-vehicle input { flex-shrink: 0; accent-color: #0d9c53; }
        .protocol-vehicle img { width: 64px; height: 42px; object-fit: contain; flex-shrink: 0; }
        .protocol-vehicle-info { flex: 1; min-width: 0; line-height: 1.25; }
        .protocol-vehicle-info strong { display: block; font-size: .9rem; color: #14201b; }
        .protocol-vehicle-info small { color: #5d6b64; font-size: .78rem; white-space: nowrap; }
        .protocol-vehicle-meta { display: flex; flex-wrap: wrap; align-items: baseline; gap: 2px 10px; margin-top: 2px; }
        .protocol-vehicle-price { font-weight: 700; color: #0d1883; font-size: .88rem; white-space: nowrap; }
        .protocol-vehicles-note { font-size: .76rem; color: #8a5a00; background: #fff8e6; border: 1px solid #f3dfa8; border-radius: 6px; padding: 6px 9px; }
    </style>
@endonce

<div class="protocol-vehicles protocol-hide" id="vehicles{{ $suffix }}" data-vehicle-picker="{{ $suffix }}">
    <div class="protocol-vehicles-title">Choose a vehicle</div>
    @foreach (\App\Support\ProtocolVehicles::all() as $key => $vehicle)
        <label class="protocol-vehicle">
            <input type="radio" name="optional_vehicle{{ $suffix }}" value="{{ $key }}">
            <img src="{{ asset($vehicle['image']) }}" alt="{{ $vehicle['name'] }}">
            <span class="protocol-vehicle-info">
                <strong>{{ $vehicle['name'] }}</strong>
                <span class="protocol-vehicle-meta">
                    <span class="protocol-vehicle-price">{{ \App\Support\ProtocolVehicles::priceLabel($vehicle['price']) }}</span>
                    <small><x-ph-icon name="users" /> Up to {{ $vehicle['seats'] }} seats</small>
                </span>
            </span>
        </label>
    @endforeach
    <div class="protocol-vehicles-note">Vehicle price is not included in the protocol amount; it is paid separately.</div>
</div>
