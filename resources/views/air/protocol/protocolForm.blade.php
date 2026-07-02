@component('layouts.app', ['title' => 'Protocol Booking Form - TravelWheel'])
<style>
    .hidden { display: none; }
    .selectable { cursor: pointer; border: 2px solid transparent; }
    .selectable:hover { opacity: 0.6; border: 1px solid rgba(13,24,131,1); border-radius: 5px; }
    .selected { border: 2px solid rgba(13,24,131,1); }
    .optional-display { border: 1px solid #ccc; padding: 10px; border-radius: 5px; }
</style>

<section class="shadow-sm py-4">
    <div class="container">
        <div class="row pt-3">
            <div class="col-sm-6 p-3">
                <h3><img src="{{ asset('assets/img/pp.png') }}" class="img-fluid me-2" style="width:30px;" alt="protocol"> Airport Protocol Service</h3>
            </div>
        </div>

        <div class="row shadow p-4 mb-5">
            <div class="col-sm-5 mb-3">
                <div class="bg-light p-3 shadow-sm">
                    <p style="color:rgba(13,156,83,1);"><b>Benefits of Our Protocol Services for All Nigeria Airports</b></p>
                    <ul class="list-unstyled">
                        <li><i class="fa fa-check"></i> Meet and Greet.</li>
                        <li><i class="fa fa-check"></i> Exclusive Baggage Handling.</li>
                        <li><i class="fa fa-check"></i> No Queue.</li>
                        <li><i class="fa fa-check"></i> Fast-tracking Check-in Process.</li>
                        <li><i class="fa fa-check"></i> Stress free Check-in Process.</li>
                        <li><i class="fa fa-check"></i> Escort through Boarding Gate.</li>
                        <li><i class="fa fa-check"></i> Escort to the Arrival Lobby.</li>
                        <li><i class="fa fa-check"></i> Cordinate Passenger to their Pre-arranged Transportation.</li>
                        <li><i class="fa fa-check"></i> Other relevant Airport Protocol Service as case may be.</li>
                        <li><i class="fa fa-angle-right" style="font-size:20px;"></i> Pick-up Request (Optional)</li>
                        <li><i class="fa fa-angle-right" style="font-size:20px;"></i> Drop-off Request (Optional)</li>
                        <li><i class="fa fa-angle-right" style="font-size:20px;"></i> Police Escort Request (Optional)</li>
                    </ul>
                    <h6>NB: <b style="color:red;">A Protocol Boarding Pass will be generated after a Successful transaction.</b></h6>
                </div>
            </div>

            <input type="hidden" id="airport3" value="{{ $data['airport'] }}">

            @if($data['airport'] === 'International Airport')
            <div class="col-sm-7" id="international">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0" style="color:rgba(13,156,83,1);">Flight Details</h6>
                        <small class="text-muted">Fill in details</small>
                    </div>
                    <div class="card-body">
                        <form id="myForm" action="{{ route('air.protocol_checkout') }}" method="POST">
                            @csrf
                            <div class="row">
                                <input type="hidden" value="{{ request('plan') }}" name="plan" id="plan">
                                <input type="hidden" id="selectedAmount" name="amount">
                                <div class="col-sm-6 mb-3">
                                    <label class="form-label">Location</label>
                                    <input type="text" class="form-control" value="{{ $data['location'] }}" readonly>
                                    <input type="hidden" name="location" value="{{ $data['location'] }}">
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <label class="form-label">Airport</label>
                                    <input type="text" class="form-control" value="{{ $data['airport'] }}" readonly>
                                    <input type="hidden" name="airport" value="{{ $data['airport'] }}">
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <label class="form-label">Travel Date</label>
                                    <input class="form-control" type="date" name="travel_date" required>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <label class="form-label">{{ $data['service'] }} Time</label>
                                    <input type="time" name="d_time" class="form-control" required>
                                    <input type="hidden" name="service" value="{{ $data['service'] }}" id="service">
                                    <input type="hidden" value="{{ $data['service'] }}" id="serviceI">
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <label class="form-label">Select Airline</label>
                                    <select class="form-select" id="airlineselect1" name="airline" required>
                                        <option value="">-- Choose Airline --</option>
                                    </select>
                                </div>
                                <div class="col-sm-6 mb-3 hidden" id="other1">
                                    <label class="form-label">Other Airline</label>
                                    <input type="text" class="form-control" name="other" placeholder="Enter Airline Name">
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <label class="form-label">No. Of Person(s)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-user"></i></span>
                                        <input type="number" class="form-control" name="nop" id="numberInput" value="0" min="1" required>
                                    </div>
                                    <small id="nop-error" class="text-danger" style="display:none;">Please enter a number greater than zero.</small>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <div class="input-group">
                                        <select class="form-select" style="max-width:110px;" name="country_code">
                                            <option value="+234" selected>+234 (NG)</option>
                                            <option value="+1">+1 (US)</option>
                                            <option value="+44">+44 (UK)</option>
                                            <option value="+233">+233 (GH)</option>
                                            <option value="+254">+254 (KE)</option>
                                            <option value="+27">+27 (ZA)</option>
                                            <option value="+971">+971 (UAE)</option>
                                            <option value="+49">+49 (DE)</option>
                                            <option value="+33">+33 (FR)</option>
                                            <option value="+39">+39 (IT)</option>
                                        </select>
                                        <input type="number" class="form-control" name="phone_no" id="phone_no" placeholder="Phone number" required>
                                    </div>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <label class="form-label">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                                        <input type="email" class="form-control" name="email" placeholder="Email Address" required>
                                    </div>
                                </div>
                                <div class="col-sm-6 mb-3 hidden" id="arrival_I">
                                    <select class="form-select" name="optinal_requestA">
                                        <option value="">-- Optional Request --</option>
                                        <option value="Drop-off">Drop-off Request</option>
                                        <option value="Police Escort">Police Escort Request</option>
                                        <option value="Drop-off & Escort">Drop-off &amp; Escort</option>
                                    </select>
                                    <small class="text-danger">Optional request if applicable.</small>
                                </div>
                                <div class="col-sm-6 mb-3 hidden" id="departure_I">
                                    <select class="form-select" name="optinal_requestD">
                                        <option value="">-- Optional Request --</option>
                                        <option value="Pick-up">Pick-up Request</option>
                                        <option value="Police Escort">Police Escort Request</option>
                                        <option value="Pick-up & Escort">Pick-up &amp; Escort</option>
                                    </select>
                                    <small class="text-danger">Optional request if applicable.</small>
                                </div>
                                <input type="hidden" name="optionalPriceA" id="optionalPriceA">
                                <input type="hidden" name="optionalPriceD" id="optionalPriceD">

                                <div class="col-sm-6 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="terms" id="terms1" required>
                                        <label class="form-check-label" for="terms1">I agree to Terms &amp; Services.</label>
                                    </div>
                                </div>
                                <div class="col-sm-12 text-center mb-3">
                                    <p>Amount: NGN <span id="textValue">0</span></p>
                                    <input type="hidden" id="amountpriceI" value="{{ $price }}">
                                </div>
                                <div class="text-center">
                                    <button type="submit" class="btn btn-success px-5">Book Service</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            @elseif($data['airport'] === 'Local Airport')
            <div class="col-sm-7" id="local">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0" style="color:rgba(13,156,83,1);">Flight Details</h6>
                        <small class="text-muted">Fill in details</small>
                    </div>
                    <div class="card-body">
                        <form id="myFormL" action="{{ route('air.protocol_checkout') }}" method="POST">
                            @csrf
                            <div class="row">
                                <input type="hidden" id="selectedAmountL" name="amount">
                                <input type="hidden" name="plan" id="planL" value="{{ request('plan') }}">
                                <div class="col-sm-6 mb-3">
                                    <label class="form-label">Location</label>
                                    <input type="text" class="form-control" value="{{ $data['location'] }}" readonly>
                                    <input type="hidden" name="location" id="location" value="{{ $data['location'] }}">
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <label class="form-label">Airport</label>
                                    <input type="text" class="form-control" value="{{ $data['airport'] }}" readonly>
                                    <input type="hidden" name="airport" id="airport" value="{{ $data['airport'] }}">
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <label class="form-label">Travel Date</label>
                                    <input class="form-control" type="date" name="travel_date" required>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <label class="form-label">{{ $data['service'] }} Time</label>
                                    <input type="time" name="d_time" class="form-control" required>
                                    <input type="hidden" id="service" name="service" value="{{ $data['service'] }}">
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <label class="form-label">Select Airline</label>
                                    <select class="form-select" id="airlineselect2" name="airline" required>
                                        <option value="">-- Choose Airline --</option>
                                    </select>
                                </div>
                                <div class="col-sm-6 mb-3 hidden" id="other2">
                                    <label class="form-label">Other Airline</label>
                                    <input type="text" class="form-control" name="other" value="">
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <label class="form-label">No. Of Person(s)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-user"></i></span>
                                        <input type="number" class="form-control" name="nop" id="numberInputL" value="0" min="1" required>
                                    </div>
                                    <small id="nop-errorL" class="text-danger" style="display:none;">Please enter a number greater than zero.</small>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <div class="input-group">
                                        <select class="form-select" style="max-width:110px;" name="country_code">
                                            <option value="+234" selected>+234 (NG)</option>
                                            <option value="+1">+1 (US)</option>
                                            <option value="+44">+44 (UK)</option>
                                            <option value="+233">+233 (GH)</option>
                                            <option value="+254">+254 (KE)</option>
                                        </select>
                                        <input type="number" class="form-control" name="phone_no" id="phone_no2" placeholder="Phone number" required>
                                    </div>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <label class="form-label">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                                        <input type="email" class="form-control" name="email" placeholder="Email Address" required>
                                    </div>
                                </div>
                                <div class="col-sm-6 mb-3 hidden" id="departure_L">
                                    <select class="form-select" name="optinal_requestD2">
                                        <option value="">-- Optional Request --</option>
                                        <option value="Pick-up">Pick-up Request</option>
                                        <option value="Police Escort">Police Escort Request</option>
                                        <option value="Pick-up & Escort">Pick-up &amp; Escort</option>
                                    </select>
                                    <small class="text-danger">Optional request if applicable.</small>
                                </div>
                                <div class="col-sm-6 mb-3 hidden" id="arrival_L">
                                    <select class="form-select" name="optinal_requestA2">
                                        <option value="">-- Optional Request --</option>
                                        <option value="Drop-off">Drop-off Request</option>
                                        <option value="Police Escort">Police Escort Request</option>
                                        <option value="Drop-off & Escort">Drop-off &amp; Escort</option>
                                    </select>
                                    <small class="text-danger">Optional request if applicable.</small>
                                </div>
                                <input type="hidden" name="optionalPriceD2" id="optionalPriceD2">
                                <input type="hidden" name="optionalPriceA2" id="optionalPriceA2">

                                <div class="col-sm-6 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="terms" id="terms2" required>
                                        <label class="form-check-label" for="terms2">I agree to Terms &amp; Services.</label>
                                    </div>
                                </div>
                                <div class="col-sm-12 text-center mb-3">
                                    <p>Amount: NGN <span id="textValueL">0</span></p>
                                    <input type="hidden" id="amountprice" value="{{ $price }}">
                                </div>
                                <div class="text-center">
                                    <button type="submit" class="btn btn-success px-5">Proceed</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- International airline dropdown ---
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
        select1.addEventListener('change', () => { document.getElementById('other1').classList.toggle('hidden', select1.value !== 'OTHERS'); });
    }

    // --- Local airline dropdown ---
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
        select2.addEventListener('change', () => { document.getElementById('other2').classList.toggle('hidden', select2.value !== 'OTHERS'); });
    }

    // --- Show optional request based on service ---
    const serviceEl = document.getElementById('service') || document.getElementById('serviceI');
    if (serviceEl) {
        const svc = serviceEl.value;
        const arrI = document.getElementById('arrival_I');
        const depI = document.getElementById('departure_I');
        const arrL = document.getElementById('arrival_L');
        const depL = document.getElementById('departure_L');
        if (svc === 'Arrival') {
            if (arrI) arrI.style.display = 'block';
            if (depI) depI.style.display = 'none';
            if (arrL) arrL.style.display = 'block';
            if (depL) depL.style.display = 'none';
        } else if (svc === 'Departure') {
            if (arrI) arrI.style.display = 'none';
            if (depI) depI.style.display = 'block';
            if (arrL) arrL.style.display = 'none';
            if (depL) depL.style.display = 'block';
        }
    }

    // --- Amount calculation (International) ---
    const numberInput = document.getElementById('numberInput');
    const textValue   = document.getElementById('textValue');
    const selectedAmt = document.getElementById('selectedAmount');
    const amtPriceI   = document.getElementById('amountpriceI');
    if (numberInput && amtPriceI) {
        numberInput.addEventListener('input', function () {
            const total = parseInt(numberInput.value) * parseFloat(amtPriceI.value);
            if (textValue) textValue.textContent = isNaN(total) ? '0' : total.toLocaleString('en-US');
            if (selectedAmt) selectedAmt.value = isNaN(total) ? 0 : total;
        });
    }

    // --- Amount calculation (Local) ---
    const numberInputL = document.getElementById('numberInputL');
    const textValueL   = document.getElementById('textValueL');
    const selectedAmtL = document.getElementById('selectedAmountL');
    const amtPrice     = document.getElementById('amountprice');
    if (numberInputL && amtPrice) {
        numberInputL.addEventListener('input', function () {
            const total = parseInt(numberInputL.value) * parseFloat(amtPrice.value);
            if (textValueL) textValueL.textContent = isNaN(total) ? '0' : total.toLocaleString('en-US');
            if (selectedAmtL) selectedAmtL.value = isNaN(total) ? 0 : total;
        });
    }
});
</script>
@endcomponent
