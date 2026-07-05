@component('layouts.app', ['title' => 'Lounge Booking - TravelWheel'])
@include('air.lounge.partials.lounge-ui')

<style>
    .lounge-dropdown-item { display:flex; justify-content:space-between; align-items:center; }
    .lounge-items-controls { display:flex; align-items:center; }
    .lounge-item-count { font-size:12px; margin:0 5px; }
    .lounge-increment-button, .lounge-decrement-button { padding:4px 8px; font-size:12px; border-radius:10px; border:none; }
    .lounge-solid { border:1px solid #d7e2dc; border-radius:8px; }
</style>

<section class="lounge-page">
    <div class="lounge-wrap">
        @foreach($lounges as $lounge)
            @php $airport = $lounge->airport == 1 ? 'International' : 'Local'; @endphp

            <div class="lounge-steps">
                <span class="lounge-step"><x-ph-icon name="map-pin" /> {{ $lounge->brand_name }}</span>
                <span class="lounge-step lounge-step-active"><x-ph-icon name="identification-card" /> Add details</span>
                <span class="lounge-step"><x-ph-icon name="credit-card" /> Pay</span>
            </div>

            <div class="lounge-grid">
                <div>
                    <div class="lounge-hero-main" style="padding:0; overflow:hidden;">
                        <div id="carouselBook{{ $lounge->id }}" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                <div class="carousel-item active">
                                    <img src="{{ asset('assets/lounge/' . $lounge->pics1) }}" class="d-block w-100" style="aspect-ratio:4/3; object-fit:cover;" alt="">
                                </div>
                                <div class="carousel-item">
                                    <img src="{{ asset('assets/lounge/' . $lounge->pics2) }}" class="d-block w-100" style="aspect-ratio:4/3; object-fit:cover;" alt="">
                                </div>
                                <div class="carousel-item">
                                    <img src="{{ asset('assets/lounge/' . $lounge->pics3) }}" class="d-block w-100" style="aspect-ratio:4/3; object-fit:cover;" alt="">
                                </div>
                            </div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#carouselBook{{ $lounge->id }}" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon"></span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#carouselBook{{ $lounge->id }}" data-bs-slide="next">
                                <span class="carousel-control-next-icon"></span>
                            </button>
                        </div>
                    </div>

                    <div class="lounge-panel mt-3">
                        <h1 class="lounge-title">{{ $lounge->brand_name }}</h1>
                        <div class="lounge-kicker"><x-ph-icon name="map-pin" /> {{ $lounge->description }}</div>

                        <div class="lounge-panel-title mt-3"><x-ph-icon name="lifebuoy" /> Facilities</div>
                        <ul class="lounge-list">
                            <li><x-ph-icon name="wifi-high" /> {{ $lounge->facilities1 }}</li>
                            <li><x-ph-icon name="hamburger" /> {{ $lounge->facilities2 }}</li>
                            <li><x-ph-icon name="couch" /> {{ $lounge->facilities3 }}</li>
                            <li><x-ph-icon name="newspaper" /> {{ $lounge->facilities4 }}</li>
                            <li><x-ph-icon name="bell" /> {{ $lounge->facilities5 }}</li>
                        </ul>
                    </div>
                </div>

                <div class="lounge-panel">
                    <div class="lounge-panel-title"><x-ph-icon name="identification-card" /> Personal Details</div>

                    <form id="myForm" action="{{ route('air.loungecheckout') }}" method="POST">
                        @csrf
                        <input type="hidden" name="lounge" value="{{ $lounge->brand_name }}">
                        <input type="hidden" name="state" value="{{ $lounge->location }}">
                        <input type="hidden" name="airport" id="airport" value="{{ $airport }}">

                        <div class="row">
                            <div class="col-sm-6 lounge-field">
                                <label class="lounge-label">Location</label>
                                <input type="text" class="form-control" value="{{ $lounge->location }}" readonly>
                            </div>
                            <div class="col-sm-6 lounge-field">
                                <label class="lounge-label">Airport</label>
                                <input type="text" class="form-control" value="{{ $airport }}" readonly>
                            </div>
                            <div class="col-sm-6 lounge-field">
                                <label class="lounge-label">First Name</label>
                                <input type="text" class="form-control" name="firstname" placeholder="Enter first name" required>
                            </div>
                            <div class="col-sm-6 lounge-field">
                                <label class="lounge-label">Last Name</label>
                                <input type="text" class="form-control" name="lastname" placeholder="Enter last name" required>
                            </div>
                            <div class="col-sm-6 lounge-field">
                                <label class="lounge-label">Phone Number</label>
                                <div class="input-group">
                                    <select class="form-select" name="country_code" style="max-width:100px;">
                                        <option value="+234" selected>+234 (Nigeria)</option>
                                        <option value="+1">+1 (US/Canada)</option>
                                        <option value="+44">+44 (UK)</option>
                                        <option value="+233">+233 (Ghana)</option>
                                    </select>
                                    <input type="number" class="form-control" name="phone_no" id="phone_no" placeholder="Phone number" minlength="10" maxlength="10" required>
                                </div>
                                <small id="phone-error" class="text-danger" style="display:none;">Phone number must be exactly 10 digits.</small>
                            </div>
                            <div class="col-sm-6 lounge-field">
                                <label class="lounge-label">Email Address</label>
                                <input type="email" class="form-control" name="email" placeholder="Email Address" required>
                            </div>
                            <div class="col-sm-6 lounge-field">
                                <label class="lounge-label">Service Date</label>
                                <input type="date" class="form-control" name="service_date" required>
                            </div>
                            <div class="col-sm-6 lounge-field">
                                <label class="lounge-label">Departure Time</label>
                                <input type="time" class="form-control" name="d_time" required>
                            </div>

                            {{-- International airlines --}}
                            <div class="col-sm-6 lounge-field {{ $airport === 'International' ? '' : 'lounge-hide' }}" id="airline1Div">
                                <label class="lounge-label">Select Airline</label>
                                <select class="form-select" id="airlineselect1" name="airline1">
                                    <option value="">-- Choose Airline --</option>
                                    <option value="AIR COTE D'IVOIRE">AIR COTE D'IVOIRE</option>
                                    <option value="ARIK AIR">ARIK AIR</option>
                                    <option value="ASKY AIRLINES">ASKY AIRLINES</option>
                                    <option value="AIR FRANCE">AIR FRANCE</option>
                                    <option value="AIR NAMIBIA">AIR NAMIBIA</option>
                                    <option value="BRITISH AIRWAYS">BRITISH AIRWAYS</option>
                                    <option value="DELTA AIRLINES">DELTA AIRLINES</option>
                                    <option value="Egypt Airline">Egypt Airline</option>
                                    <option value="Emirates Airlines">Emirates Airlines</option>
                                    <option value="ETHIOPIAN AIRLINES">ETHIOPIAN AIRLINES</option>
                                    <option value="ETIHAD AIRWAYS">ETIHAD AIRWAYS</option>
                                    <option value="KENYA AIRWAYS">KENYA AIRWAYS</option>
                                    <option value="KLM">KLM</option>
                                    <option value="LUFTHANSA">LUFTHANSA</option>
                                    <option value="QATAR AIRWAYS">QATAR AIRWAYS</option>
                                    <option value="ROYAL AIR MAROC">ROYAL AIR MAROC</option>
                                    <option value="RWANDA AIR">RWANDA AIR</option>
                                    <option value="SOUTH AFRICAN AIRWAYS">SOUTH AFRICAN AIRWAYS</option>
                                    <option value="TURKISH AIRLINE">TURKISH AIRLINE</option>
                                    <option value="VIRGIN ATLANTIC">VIRGIN ATLANTIC</option>
                                    <option value="TAP PORTUGAL">TAP PORTUGAL</option>
                                    <option value="AFRICAN WORLD AIRLINES">AFRICAN WORLD AIRLINES</option>
                                    <option value="MID AFRICA AIRLINES">MID AFRICA AIRLINES</option>
                                    <option value="SAUDI ARABIAN AIRLINE">SAUDI ARABIAN AIRLINE</option>
                                    <option value="AIRPEACE">AIRPEACE</option>
                                    <option value="OTHERS">OTHERS</option>
                                </select>
                            </div>
                            <div class="col-sm-6 lounge-field lounge-hide" id="other1Div">
                                <label class="lounge-label">Other Airline (International)</label>
                                <input type="text" class="form-control" name="other1" placeholder="Enter airline name">
                            </div>

                            {{-- Local airlines --}}
                            <div class="col-sm-6 lounge-field {{ $airport === 'Local' ? '' : 'lounge-hide' }}" id="airline2Div">
                                <label class="lounge-label">Select Airline</label>
                                <select class="form-select" id="airlineselect2" name="airline2">
                                    <option value="">-- Choose Airline --</option>
                                    <option value="AIR PEACE">AIR PEACE</option>
                                    <option value="DANA AIR">DANA AIR</option>
                                    <option value="MAX AIR">MAX AIR</option>
                                    <option value="OVERLAND AIRWAYS">OVERLAND AIRWAYS</option>
                                    <option value="AERO">AERO</option>
                                    <option value="IBOM AIR">IBOM AIR</option>
                                    <option value="UNITED NIGERIA">UNITED NIGERIA</option>
                                    <option value="AZMAN">AZMAN</option>
                                    <option value="ARIK">ARIK</option>
                                    <option value="GREEN AFRICA">GREEN AFRICA</option>
                                    <option value="VALUE JET">VALUE JET</option>
                                    <option value="FIRST NATION AIRLINE">FIRST NATION AIRLINE</option>
                                    <option value="IRS AIRLINE">IRS AIRLINE</option>
                                    <option value="KABO AIR">KABO AIR</option>
                                    <option value="OTHERS">OTHERS</option>
                                </select>
                            </div>
                            <div class="col-sm-6 lounge-field lounge-hide" id="other2Div">
                                <label class="lounge-label">Other Airline (Local)</label>
                                <input type="text" class="form-control" name="other2" placeholder="Enter airline name">
                            </div>

                            <div class="col-sm-6 lounge-field">
                                <label class="lounge-label">No. Of Person(s)</label>
                                <div class="dropdown" data-bs-dropdown="true">
                                    <a class="dropdown-toggle lounge-solid p-2 text-muted d-block" href="#" role="button"
                                       data-bs-toggle="dropdown" aria-expanded="false" style="text-decoration:none; min-height:46px; display:flex; align-items:center;">
                                        <x-ph-icon name="users" /><span class="ps-2 pe-2">No. of Person(s)</span>
                                        <input type="number" class="ms-2" value="0" name="nop" id="totalValue" readonly
                                               style="border:none; background:none; width:50px;">
                                    </a>
                                    <ul class="dropdown-menu ps-2 pe-2">
                                        <li>
                                            <a class="dropdown-item lounge-dropdown-item pe-3 ps-2" href="#" data-bs-auto-close="false" data-item-type="Adult">
                                                <span><x-ph-icon name="user" /> Adult(s) <small>+12 yrs</small></span>
                                                <div class="lounge-items-controls pe-2 ps-3">
                                                    <button class="lounge-decrement-button" type="button">-</button>
                                                    <span class="lounge-item-count">0</span>
                                                    <button class="lounge-increment-button" type="button">+</button>
                                                </div>
                                                <input type="hidden" id="adultValue" name="adultValue" value="0">
                                                <input type="hidden" id="adultAmount" name="adultAmount" value="0">
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item lounge-dropdown-item pe-3 ps-2" href="#" data-bs-auto-close="false" data-item-type="Child">
                                                <span><x-ph-icon name="baby" /> Child(s) <small>2-11 yrs</small></span>
                                                <div class="lounge-items-controls pe-2 ps-3">
                                                    <button class="lounge-decrement-button" type="button">-</button>
                                                    <span class="lounge-item-count">0</span>
                                                    <button class="lounge-increment-button" type="button">+</button>
                                                </div>
                                                <input type="hidden" id="childValue" name="childValue" value="0">
                                                <input type="hidden" id="childAmount" name="childAmount" value="0">
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item lounge-dropdown-item pe-3 ps-2" href="#" data-bs-auto-close="false" data-item-type="Infant">
                                                <span><x-ph-icon name="baby" /> Infant(s) <small>0-2 yrs</small></span>
                                                <div class="lounge-items-controls pe-2 ps-3">
                                                    <button class="lounge-decrement-button" type="button">-</button>
                                                    <span class="lounge-item-count">0</span>
                                                    <button class="lounge-increment-button" type="button">+</button>
                                                </div>
                                                <input type="hidden" id="infantValue" name="infantValue" value="0">
                                                <input type="hidden" id="infantAmount" name="infantAmount" value="0">
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                @error('nop')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <input type="hidden" id="selectedAmount" name="amount" value="0">
                        <input type="hidden" id="selectAmountA" value="{{ $lounge->priceA }}">
                        <input type="hidden" id="selectAmountB" value="{{ $lounge->priceB ?? 0 }}">
                        <input type="hidden" id="selectAmountC" value="{{ $lounge->priceC ?? 0 }}">

                        <div class="lounge-subpanel mt-3">
                            <div class="lounge-total-row">
                                <span>Total Amount</span>
                                <strong>NGN <span id="textValue">0.00</span></strong>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="tnc" required>
                                <label class="form-check-label" for="tnc">I agree to the Terms &amp; Services.</label>
                            </div>
                            <button type="submit" class="lounge-btn w-100">
                                Book Service <x-ph-icon name="arrow-right" />
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const incrementButtons = document.querySelectorAll('.lounge-increment-button');
    const decrementButtons = document.querySelectorAll('.lounge-decrement-button');
    const selectAmountA = document.getElementById('selectAmountA');
    const selectAmountB = document.getElementById('selectAmountB');
    const selectAmountC = document.getElementById('selectAmountC');
    const textValue = document.getElementById('textValue');

    incrementButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault(); e.stopPropagation();
            const span = btn.parentElement.querySelector('.lounge-item-count');
            span.textContent = parseInt(span.textContent) + 1;
            updateTotals();
        });
    });
    decrementButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault(); e.stopPropagation();
            const span = btn.parentElement.querySelector('.lounge-item-count');
            const cur = parseInt(span.textContent);
            if (cur > 0) { span.textContent = cur - 1; updateTotals(); }
        });
    });

    function updateTotals() {
        const adultCount  = parseInt(document.querySelector("[data-item-type='Adult'] .lounge-item-count").textContent);
        const childCount  = parseInt(document.querySelector("[data-item-type='Child'] .lounge-item-count").textContent);
        const infantCount = parseInt(document.querySelector("[data-item-type='Infant'] .lounge-item-count").textContent);
        const priceA = parseFloat(selectAmountA.value) || 0;
        const priceB = parseFloat(selectAmountB.value) || 0;
        const priceC = parseFloat(selectAmountC.value) || 0;

        const amtA = priceA * adultCount;
        const amtB = priceB * childCount;
        const amtC = priceC * infantCount;
        const total = adultCount + childCount + infantCount;

        document.getElementById('adultValue').value   = adultCount;
        document.getElementById('childValue').value   = childCount;
        document.getElementById('infantValue').value  = infantCount;
        document.getElementById('adultAmount').value  = amtA;
        document.getElementById('childAmount').value  = amtB;
        document.getElementById('infantAmount').value = amtC;
        document.getElementById('totalValue').value   = total;
        document.getElementById('selectedAmount').value = amtA + amtB + amtC;
        textValue.textContent = (amtA + amtB + amtC).toLocaleString('en-US');
    }

    // Airline visibility
    const airportVal  = document.getElementById('airport').value;
    const airline1Div = document.getElementById('airline1Div');
    const airline2Div = document.getElementById('airline2Div');
    const other1Div   = document.getElementById('other1Div');
    const other2Div   = document.getElementById('other2Div');
    const sel1 = document.getElementById('airlineselect1');
    const sel2 = document.getElementById('airlineselect2');

    if (airportVal === 'International') { airline1Div.classList.remove('lounge-hide'); }
    else if (airportVal === 'Local')    { airline2Div.classList.remove('lounge-hide'); }

    sel1.addEventListener('change', function () {
        other1Div.classList.toggle('lounge-hide', this.value !== 'OTHERS');
    });
    sel2.addEventListener('change', function () {
        other2Div.classList.toggle('lounge-hide', this.value !== 'OTHERS');
    });

    // Phone validation
    document.getElementById('myForm').addEventListener('submit', function (e) {
        const phoneInput = document.getElementById('phone_no');
        if (phoneInput.value.length !== 10) {
            document.getElementById('phone-error').style.display = 'inline';
            e.preventDefault();
        } else {
            document.getElementById('phone-error').style.display = 'none';
        }
    });
});
</script>

@endcomponent
