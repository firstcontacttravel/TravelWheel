@component('layouts.app', ['title' => 'Protocol Booking Form - TravelWheel'])
@include('air.protocol.partials.protocol-ui')

@php
    $isInternational = $data['airport'] === 'International Airport';
    $showDeparture = $data['service'] === 'Departure';
    $showArrival = $data['service'] === 'Arrival';

    if ($isInternational) {
        if ($showArrival) {
            $formBenefits = ['Meet and greet', 'Exclusive baggage handling', 'Escort to the arrival lobby', 'Coordinate passenger to pre-arranged transportation', 'Other relevant airport protocol service'];
            $formPlanLabel = 'VIP';
        } elseif ($plan == '2') {
            $formBenefits = ['Meet and greet', 'Exclusive baggage handling', 'No queuing', 'Stress-free check-in process', 'Other relevant airport protocol service'];
            $formPlanLabel = 'Regular';
        } else {
            $formBenefits = ['Meet and greet', 'Exclusive baggage handling', 'Fast-tracking check-in process', 'No queuing', 'Stress-free check-in process', 'Escort through boarding gate', 'Other relevant airport protocol service'];
            $formPlanLabel = 'VIP';
        }
    } else {
        $formBenefits = $showDeparture
            ? ['Meet and greet', 'Exclusive baggage handling', 'Fast-tracking check-in process', 'No queuing', 'Pre check-in process', 'Other relevant airport protocol service']
            : ['Meet and greet', 'Exclusive baggage handling', 'Escort to the arrival lobby', 'Coordinate passenger to pre-arranged transportation', 'Other relevant airport protocol service'];
        $formPlanLabel = null;
    }
@endphp

<section class="protocol-page">
    <div class="protocol-wrap">
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <div class="protocol-steps">
            <span class="protocol-step"><x-ph-icon name="map-pin" /> Select airport</span>
            <span class="protocol-step"><x-ph-icon name="tag" /> Choose plan</span>
            <span class="protocol-step protocol-step-active"><x-ph-icon name="identification-card" /> Add details</span>
            <span class="protocol-step"><x-ph-icon name="credit-card" /> Pay</span>
        </div>

        <div class="protocol-grid">
            <div>
                <div class="protocol-hero-main">
                    <div class="protocol-kicker"><x-ph-icon name="clipboard-text" /> Booking Details</div>
                    <h1 class="protocol-title">{{ $data['service'] }} protocol request</h1>
                    <p class="protocol-copy">
                        Complete your flight and contact details. 
                    </p>
                </div>

                <div class="protocol-panel mt-3">
                    <div class="protocol-panel-title"><x-ph-icon name="info" /> Selected Service</div>
                    <div class="protocol-detail-grid" style="grid-template-columns:1fr;">
                        <div class="protocol-detail"><span>Location</span><strong>{{ $data['location'] }}</strong></div>
                        <div class="protocol-detail"><span>Airport</span><strong>{{ $data['airport'] }}</strong></div>
                        <div class="protocol-detail"><span>Segment</span><strong>{{ $data['service'] }}</strong></div>
                    </div>
                    
                    <div class="protocol-panel-title mt-4">
                        <x-ph-icon name="sparkle" /> What Your Service Covers
                        @if($formPlanLabel)
                            <span class="protocol-chip">{{ $formPlanLabel }}</span>
                        @endif
                    </div>
                    <ul class="protocol-list">
                        @foreach($formBenefits as $benefit)
                            <li><x-ph-icon name="check-circle" /> {{ $benefit }}</li>
                        @endforeach
                    </ul>
                    <div class="protocol-note">A protocol boarding pass is generated after successful payment.</div>
                
                </div>

            </div>

            <div class="protocol-panel">
                <div class="protocol-panel-title"><x-ph-icon name="airplane-in-flight" /> Flight Details</div>

                @if($data['airport'] === 'International Airport')
                    <form id="myForm" action="{{ route('air.protocol_checkout') }}" method="POST">
                        @csrf
                        <input type="hidden" value="{{ request('plan') }}" name="plan" id="plan">
                        <input type="hidden" id="selectedAmount" name="amount">
                        <input type="hidden" name="location" value="{{ $data['location'] }}">
                        <input type="hidden" name="airport" value="{{ $data['airport'] }}">
                        <input type="hidden" name="service" value="{{ $data['service'] }}" id="service">
                        <input type="hidden" value="{{ $data['service'] }}" id="serviceI">

                        <div class="row">
                            <div class="col-sm-6 protocol-field">
                                <label class="form-label">Travel Date</label>
                                <input class="form-control" type="date" name="travel_date" required>
                            </div>
                            <div class="col-sm-6 protocol-field">
                                <label class="form-label">{{ $data['service'] }} Time</label>
                                <input type="time" name="d_time" class="form-control" required>
                            </div>
                            <div class="col-sm-6 protocol-field">
                                <label class="form-label">Airline</label>
                                <select class="form-select" id="airlineselect1" name="airline" required>
                                    <option value="">Choose airline</option>
                                </select>
                            </div>
                            <div class="col-sm-6 protocol-field protocol-hide" id="other1">
                                <label class="form-label">Other Airline</label>
                                <input type="text" class="form-control" name="other" placeholder="Enter airline name">
                            </div>
                            <div class="col-sm-6 protocol-field">
                                <label class="form-label">No. of Person(s)</label>
                                <input type="number" class="form-control" name="nop" id="numberInput" value="1" min="1" required>
                                <small id="nop-error" class="text-danger protocol-hide">Please enter a number greater than zero.</small>
                            </div>
                            <div class="col-sm-6 protocol-field">
                                <label class="form-label">Phone Number</label>
                                <div class="input-group">
                                    <select class="form-select" style="max-width:120px;" name="country_code">
                                        <option value="+234" selected>+234</option>
                                        <option value="+1">+1</option>
                                        <option value="+44">+44</option>
                                        <option value="+233">+233</option>
                                        <option value="+254">+254</option>
                                        <option value="+27">+27</option>
                                        <option value="+971">+971</option>
                                        <option value="+49">+49</option>
                                        <option value="+33">+33</option>
                                        <option value="+39">+39</option>
                                    </select>
                                    <input type="number" class="form-control" name="phone_no" id="phone_no" placeholder="Phone number" required>
                                </div>
                            </div>
                            <div class="col-sm-6 protocol-field">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" name="email" placeholder="Email address" required>
                            </div>
                            <div class="col-sm-6 protocol-field protocol-hide" id="arrival_I">
                                <label class="form-label">Optional Request</label>
                                <select class="form-select" name="optinal_requestA">
                                    <option value="">No optional request</option>
                                    <option value="Drop-off">Drop-off Request</option>
                                    <option value="Police Escort">Police Escort Request</option>
                                    <option value="Drop-off & Escort">Drop-off &amp; Escort</option>
                                </select>
                                @include('air.protocol.partials.vehicle-picker', ['suffix' => 'A'])
                            </div>
                            <div class="col-sm-6 protocol-field protocol-hide" id="departure_I">
                                <label class="form-label">Optional Request</label>
                                <select class="form-select" name="optinal_requestD">
                                    <option value="">No optional request</option>
                                    <option value="Pick-up">Pick-up Request</option>
                                    <option value="Police Escort">Police Escort Request</option>
                                    <option value="Pick-up & Escort">Pick-up &amp; Escort</option>
                                </select>
                                @include('air.protocol.partials.vehicle-picker', ['suffix' => 'D'])
                            </div>
                        </div>

                        <div class="protocol-subpanel mt-2">
                            <div class="protocol-total-row">
                                <span>Estimated amount</span>
                                <strong>NGN <span id="textValue">0</span></strong>
                            </div>
                            <input type="hidden" id="amountpriceI" value="{{ $price }}">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="terms" id="terms1" required>
                                <label class="form-check-label" for="terms1">I agree to Terms &amp; Services.</label>
                            </div>
                            <button type="submit" class="protocol-btn w-100">
                                Continue to Checkout <x-ph-icon name="arrow-right" />
                            </button>
                        </div>
                    </form>
                @elseif($data['airport'] === 'Local Airport')
                    <form id="myFormL" action="{{ route('air.protocol_checkout') }}" method="POST">
                        @csrf
                        <input type="hidden" id="selectedAmountL" name="amount">
                        <input type="hidden" name="plan" id="planL" value="{{ request('plan') }}">
                        <input type="hidden" name="location" id="location" value="{{ $data['location'] }}">
                        <input type="hidden" name="airport" id="airport" value="{{ $data['airport'] }}">
                        <input type="hidden" id="service" name="service" value="{{ $data['service'] }}">

                        <div class="row">
                            <div class="col-sm-6 protocol-field">
                                <label class="form-label">Travel Date</label>
                                <input class="form-control" type="date" name="travel_date" required>
                            </div>
                            <div class="col-sm-6 protocol-field">
                                <label class="form-label">{{ $data['service'] }} Time</label>
                                <input type="time" name="d_time" class="form-control" required>
                            </div>
                            <div class="col-sm-6 protocol-field">
                                <label class="form-label">Airline</label>
                                <select class="form-select" id="airlineselect2" name="airline" required>
                                    <option value="">Choose airline</option>
                                </select>
                            </div>
                            <div class="col-sm-6 protocol-field protocol-hide" id="other2">
                                <label class="form-label">Other Airline</label>
                                <input type="text" class="form-control" name="other">
                            </div>
                            <div class="col-sm-6 protocol-field">
                                <label class="form-label">No. of Person(s)</label>
                                <input type="number" class="form-control" name="nop" id="numberInputL" value="1" min="1" required>
                                <small id="nop-errorL" class="text-danger protocol-hide">Please enter a number greater than zero.</small>
                            </div>
                            <div class="col-sm-6 protocol-field">
                                <label class="form-label">Phone Number</label>
                                <div class="input-group">
                                    <select class="form-select" style="max-width:120px;" name="country_code">
                                        <option value="+234" selected>+234</option>
                                        <option value="+1">+1</option>
                                        <option value="+44">+44</option>
                                        <option value="+233">+233</option>
                                        <option value="+254">+254</option>
                                    </select>
                                    <input type="number" class="form-control" name="phone_no" id="phone_no2" placeholder="Phone number" required>
                                </div>
                            </div>
                            <div class="col-sm-6 protocol-field">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" name="email" placeholder="Email address" required>
                            </div>
                            <div class="col-sm-6 protocol-field protocol-hide" id="departure_L">
                                <label class="form-label">Optional Request</label>
                                <select class="form-select" name="optinal_requestD2">
                                    <option value="">No optional request</option>
                                    <option value="Pick-up">Pick-up Request</option>
                                    <option value="Police Escort">Police Escort Request</option>
                                    <option value="Pick-up & Escort">Pick-up &amp; Escort</option>
                                </select>
                                @include('air.protocol.partials.vehicle-picker', ['suffix' => 'D2'])
                            </div>
                            <div class="col-sm-6 protocol-field protocol-hide" id="arrival_L">
                                <label class="form-label">Optional Request</label>
                                <select class="form-select" name="optinal_requestA2">
                                    <option value="">No optional request</option>
                                    <option value="Drop-off">Drop-off Request</option>
                                    <option value="Police Escort">Police Escort Request</option>
                                    <option value="Drop-off & Escort">Drop-off &amp; Escort</option>
                                </select>
                                @include('air.protocol.partials.vehicle-picker', ['suffix' => 'A2'])
                            </div>
                        </div>

                        <div class="protocol-subpanel mt-2">
                            <div class="protocol-total-row">
                                <span>Estimated amount</span>
                                <strong>NGN <span id="textValueL">0</span></strong>
                            </div>
                            <input type="hidden" id="amountprice" value="{{ $price }}">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="terms" id="terms2" required>
                                <label class="form-check-label" for="terms2">I agree to Terms &amp; Services.</label>
                            </div>
                            <button type="submit" class="protocol-btn w-100">
                                Continue to Checkout <x-ph-icon name="arrow-right" />
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const intlAirlineMap = {
        "Lagos": ["BRITISH AIRWAYS","QATAR AIRWAYS","EMIRATES","LUFTHANSA","TURKISH AIRLINES","DELTA AIRLINES","VIRGIN ATLANTIC","ETHIOPIAN AIRLINES","KENYA AIRWAYS","AIR PEACE","ASKY AIRLINES","AIR FRANCE","KLM","EGYPT AIR","ROYAL AIR MAROC","TAAG ANGOLA","SOUTH AFRICA AIRWAYS","UNITED AIRLINES","RWANDAIR"],
        "Abuja": ["BRITISH AIRWAYS","LUFTHANSA","ETHIOPIAN AIRLINES","KLM","EMIRATES","QATAR AIRWAYS","TURKISH AIRLINES","AIR FRANCE","AIR PEACE","ASKY AIRLINES","ROYAL AIR MAROC","UNITED AIRLINES","VIRGIN ATLANTIC","DELTA AIRLINES"],
        "Kano": ["ETHIOPIAN AIRLINES"],
        "Enugu": ["ETHIOPIAN AIRLINES"],
        "Rivers": ["QATAR AIRWAYS","TURKISH AIRLINES","LUFTHANSA"]
    };

    const select1 = document.getElementById('airlineselect1');
    if (select1) {
        const location = @json($data['location'] ?? '');
        const airlines = intlAirlineMap[location] || [];
        airlines.forEach(a => { const o = document.createElement('option'); o.value = a; o.textContent = a; select1.appendChild(o); });
        const othersOpt = document.createElement('option'); othersOpt.value = 'OTHERS'; othersOpt.textContent = 'OTHERS'; select1.appendChild(othersOpt);
        select1.addEventListener('change', () => { document.getElementById('other1').classList.toggle('protocol-hide', select1.value !== 'OTHERS'); });
    }

    const localAirlineMap = {
        "Lagos": ["Air Peace","Arik","Ibom Air","Value Jet","Overland Airways","United Nigeria Airlines","Max Air","XEjet","Aero Contractor","Green Africa","Rano Air","Umza Air"],
        "Abuja": ["Air Peace","Arik","Ibom Air","Value Jet","Overland Airways","United Nigeria Airlines","Max Air","XEjet","Aero Contractor","Green Africa","Rano Air","Umza Air"],
        "Enugu": ["Ibom Air","United Nigeria Airlines","XEjet"],
        "Rivers": ["Air Peace","Arik","Aero Contractor","Ibom Air","United Nigeria Airlines","Value Jet","Umza Air"],
        "Kano": ["Air Peace","Rano Air","Value Jet","Umza Air"],
        "Delta Asaba": ["Air Peace","Arik","Aero Contractor","United Nigeria Airlines"],
        "Imo": ["Air Peace","United Nigeria Airlines","XEjet"],
        "Oyo": ["Overland Airways"],
        "Kwara": ["Overland Airways"],
        "Anambra": ["Air Peace","United Nigeria Airlines"],
        "Delta Warri": ["Air Peace","Arik","Aero Contractor"],
        "Edo": ["Air Peace","Arik","Aero Contractor","United Nigeria Airlines"],
        "Gombe": ["Air Peace"],
        "Borno": ["Air Peace","Umza Air","Rano Air"],
        "Adamawa": ["Air Peace","Umza Air"],
        "Sokoto": ["Umza Air","Rano Air"],
        "Kaduna": ["Rano Air"],
        "Cross River": ["Ibom Air","Value Jet"]
    };

    const select2 = document.getElementById('airlineselect2');
    if (select2) {
        const loc2 = document.getElementById('location') ? document.getElementById('location').value.trim() : @json($data['location'] ?? '');
        const key2 = Object.keys(localAirlineMap).find(k => k.toLowerCase() === loc2.toLowerCase());
        if (key2) {
            localAirlineMap[key2].forEach(a => { const o = document.createElement('option'); o.value = a.toUpperCase(); o.textContent = a; select2.appendChild(o); });
        }
        const othersOpt2 = document.createElement('option'); othersOpt2.value = 'OTHERS'; othersOpt2.textContent = 'OTHERS'; select2.appendChild(othersOpt2);
        select2.addEventListener('change', () => { document.getElementById('other2').classList.toggle('protocol-hide', select2.value !== 'OTHERS'); });
    }

    const serviceEl = document.getElementById('service') || document.getElementById('serviceI');
    if (serviceEl) {
        const svc = serviceEl.value;
        const arrI = document.getElementById('arrival_I');
        const depI = document.getElementById('departure_I');
        const arrL = document.getElementById('arrival_L');
        const depL = document.getElementById('departure_L');
        if (svc === 'Arrival') {
            if (arrI) arrI.classList.remove('protocol-hide');
            if (depI) depI.classList.add('protocol-hide');
            if (arrL) arrL.classList.remove('protocol-hide');
            if (depL) depL.classList.add('protocol-hide');
        } else if (svc === 'Departure') {
            if (arrI) arrI.classList.add('protocol-hide');
            if (depI) depI.classList.remove('protocol-hide');
            if (arrL) arrL.classList.add('protocol-hide');
            if (depL) depL.classList.remove('protocol-hide');
        }
    }

    function updateAmount(input, unit, output, target) {
        const qty = Math.max(1, parseInt(input.value || '1'));
        input.value = qty;
        const total = qty * parseFloat(unit.value || '0');
        output.textContent = isNaN(total) ? '0' : total.toLocaleString('en-US');
        target.value = isNaN(total) ? 0 : total;
    }

    const numberInput = document.getElementById('numberInput');
    const amountpriceI = document.getElementById('amountpriceI');
    if (numberInput && amountpriceI) {
        const textValue = document.getElementById('textValue');
        const selectedAmount = document.getElementById('selectedAmount');
        updateAmount(numberInput, amountpriceI, textValue, selectedAmount);
        numberInput.addEventListener('input', () => updateAmount(numberInput, amountpriceI, textValue, selectedAmount));
    }

    const numberInputL = document.getElementById('numberInputL');
    const amountprice = document.getElementById('amountprice');
    if (numberInputL && amountprice) {
        const textValueL = document.getElementById('textValueL');
        const selectedAmountL = document.getElementById('selectedAmountL');
        updateAmount(numberInputL, amountprice, textValueL, selectedAmountL);
        numberInputL.addEventListener('input', () => updateAmount(numberInputL, amountprice, textValueL, selectedAmountL));
    }

    // Pick-up / drop-off requests show the vehicle dropdown; a vehicle is then required
    document.querySelectorAll('[data-vehicle-picker]').forEach(picker => {
        const select = document.querySelector('select[name="optinal_request' + picker.dataset.vehiclePicker + '"]');
        if (!select) return;
        const radios = picker.querySelectorAll('input[type=radio]');
        const toggle = picker.querySelector('[data-vehicle-toggle]');
        const placeholder = picker.querySelector('[data-vehicle-placeholder]');
        const chosen = picker.querySelector('[data-vehicle-chosen]');

        const setOpen = open => {
            picker.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        };
        const showChoice = () => {
            const checked = picker.querySelector('input[type=radio]:checked');
            placeholder.classList.toggle('d-none', !!checked);
            chosen.classList.toggle('d-none', !checked);
            chosen.innerHTML = '';
            if (checked) {
                const row = checked.closest('.protocol-vehicle');
                chosen.append(row.querySelector('img').cloneNode(), row.querySelector('[data-vehicle-summary]').cloneNode(true));
                picker.classList.remove('is-invalid');
            }
        };
        const sync = () => {
            const show = /Pick-up|Drop-off/.test(select.value);
            picker.classList.toggle('protocol-hide', !show);
            radios.forEach(radio => {
                radio.required = show;
                if (!show) radio.checked = false;
            });
            if (!show) setOpen(false);
            showChoice();
        };

        toggle.addEventListener('click', () => setOpen(!picker.classList.contains('is-open')));
        radios.forEach(radio => {
            radio.addEventListener('change', () => { showChoice(); setOpen(false); toggle.focus(); });
            // Submitting without a vehicle: open the list so the browser can point at it
            radio.addEventListener('invalid', () => { picker.classList.add('is-invalid'); setOpen(true); });
        });
        document.addEventListener('click', e => { if (!picker.contains(e.target)) setOpen(false); });
        picker.addEventListener('keydown', e => { if (e.key === 'Escape') { setOpen(false); toggle.focus(); } });

        select.addEventListener('change', sync);
        sync();
    });
});
</script>
@endcomponent
