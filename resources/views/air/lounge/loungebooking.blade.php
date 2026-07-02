@component('layouts.app', ['title' => 'Lounge Booking - TravelWheel'])

<style>
    .dropdown-item { display:flex; justify-content:space-between; align-items:center; }
    .items-controls { display:flex; align-items:center; }
    .item-count { font-size:12px; margin:0 5px; }
    .increment-button, .decrement-button { padding:4px 8px; font-size:12px; border-radius:10px; border:none; }
    .solid { border:1px solid #ddd; border-radius:5px; }
</style>

<section class="shadow-sm">
    <div class="container py-4">
        @foreach($lounges as $lounge)
        <div class="row airport-form shadow p-4 mt-2 mb-5">
            <div class="col-sm-4">
                <h3>{{ $lounge->brand_name }}</h3>
                <div class="protocol mb-2">
                    <span class="text-muted">
                        <i class="fa-solid fa-location-dot" style="color:green"></i> {{ $lounge->description }}
                    </span>
                </div>
                <div class="protocol mb-3">
                    <span class="text-muted"><i class="fa-solid fa-city" style="color:green"></i> Facilities</span>
                    <ul class="list-unstyled pt-1">
                        <li class="pb-1"><i class="fas fa-wifi" style="color:green"></i> {{ $lounge->facilities1 }}</li>
                        <li class="pb-1"><i class="fas fa-hamburger" style="color:green"></i> {{ $lounge->facilities2 }}</li>
                        <li class="pb-1"><i class="fas fa-couch" style="color:green"></i> {{ $lounge->facilities3 }}</li>
                        <li class="pb-1"><i class="fas fa-newspaper" style="color:green"></i> {{ $lounge->facilities4 }}</li>
                        <li class="pb-1"><i class="fas fa-concierge-bell" style="color:green"></i> {{ $lounge->facilities5 }}</li>
                    </ul>
                </div>
                @php $airport = $lounge->airport == 1 ? 'International' : 'Local'; @endphp
                <div id="carouselBook{{ $lounge->id }}" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <div class="carousel-item active">
                            <img src="{{ asset('assets/lounge/' . $lounge->pics1) }}" class="d-block w-100" alt="">
                        </div>
                        <div class="carousel-item">
                            <img src="{{ asset('assets/lounge/' . $lounge->pics2) }}" class="d-block w-100" alt="">
                        </div>
                        <div class="carousel-item">
                            <img src="{{ asset('assets/lounge/' . $lounge->pics3) }}" class="d-block w-100" alt="">
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

            <div class="col-sm-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" style="color:rgba(13,156,83,1);">Personal Details</h5>
                        <small class="text-muted">Fill in necessary details</small>
                    </div>
                    <div class="card-body">
                        <form id="myForm" action="{{ route('air.loungecheckout') }}" method="POST">
                            @csrf
                            <input type="hidden" name="lounge" value="{{ $lounge->brand_name }}">
                            <input type="hidden" name="state" value="{{ $lounge->location }}">
                            <input type="hidden" name="airport" id="airport" value="{{ $airport }}">

                            <div class="row">
                                <div class="col-sm-6 mb-3">
                                    <label class="form-label">Location</label>
                                    <input type="text" class="form-control" value="{{ $lounge->location }}" readonly>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <label class="form-label">Airport</label>
                                    <input type="text" class="form-control" value="{{ $airport }}" readonly>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <label class="form-label">First Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-user"></i></span>
                                        <input type="text" class="form-control" name="firstname" placeholder="Enter first name" required>
                                    </div>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <label class="form-label">Last Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-user"></i></span>
                                        <input type="text" class="form-control" name="lastname" placeholder="Enter last name" required>
                                    </div>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <label class="form-label">Phone Number</label>
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
                                <div class="col-sm-6 mb-3">
                                    <label class="form-label">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                                        <input type="email" class="form-control" name="email" placeholder="Email Address" required>
                                    </div>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <label class="form-label">Service Date</label>
                                    <input type="date" class="form-control" name="service_date" required>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <label class="form-label">Departure Time</label>
                                    <input type="time" class="form-control" name="d_time" required>
                                </div>

                                {{-- International airlines --}}
                                <div class="col-sm-6 mb-3 {{ $airport === 'International' ? '' : 'd-none' }}" id="airline1Div">
                                    <label class="form-label">Select Airline</label>
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
                                <div class="col-sm-6 mb-3 d-none" id="other1Div">
                                    <label class="form-label">Other Airline (International)</label>
                                    <input type="text" class="form-control" name="other1" placeholder="Enter airline name">
                                </div>

                                {{-- Local airlines --}}
                                <div class="col-sm-6 mb-3 {{ $airport === 'Local' ? '' : 'd-none' }}" id="airline2Div">
                                    <label class="form-label">Select Airline</label>
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
                                <div class="col-sm-6 mb-3 d-none" id="other2Div">
                                    <label class="form-label">Other Airline (Local)</label>
                                    <input type="text" class="form-control" name="other2" placeholder="Enter airline name">
                                </div>

                                <div class="col-sm-6 mb-3">
                                    <label class="form-label pb-1">No. Of Person(s)</label>
                                    <div class="dropdown" data-bs-dropdown="true">
                                        <a class="dropdown-toggle solid p-2 text-muted d-block" href="#" role="button"
                                           data-bs-toggle="dropdown" aria-expanded="false" style="text-decoration:none;">
                                            <i class="fa fa-user ps-2 pe-4"></i> No. of Person(s)
                                            <input type="number" class="ms-2" value="0" name="nop" id="totalValue" readonly
                                                   style="border:none; background:none; width:50px;">
                                        </a>
                                        <ul class="dropdown-menu ps-2 pe-2">
                                            <li>
                                                <a class="dropdown-item pe-3 ps-2" href="#" data-bs-auto-close="false" data-item-type="Adult">
                                                    <i class="fa fa-user pe-2"></i> Adult(s) <small>+12 yrs</small>
                                                    <div class="items-controls pe-2 ps-3">
                                                        <button class="decrement-button">-</button>
                                                        <span class="item-count">0</span>
                                                        <button class="increment-button">+</button>
                                                    </div>
                                                    <input type="hidden" id="adultValue" name="adultValue" value="0">
                                                    <input type="hidden" id="adultAmount" name="adultAmount" value="0">
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item pe-3 ps-2" href="#" data-bs-auto-close="false" data-item-type="Child">
                                                    <i class="fa fa-child pe-2"></i> Child(s) <small>2-11 yrs</small>
                                                    <div class="items-controls pe-2 ps-3">
                                                        <button class="decrement-button">-</button>
                                                        <span class="item-count">0</span>
                                                        <button class="increment-button">+</button>
                                                    </div>
                                                    <input type="hidden" id="childValue" name="childValue" value="0">
                                                    <input type="hidden" id="childAmount" name="childAmount" value="0">
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item pe-3 ps-2" href="#" data-bs-auto-close="false" data-item-type="Infant">
                                                    <i class="fa-solid fa-baby"></i> Infant(s) <small>0-2 yrs</small>
                                                    <div class="items-controls pe-2 ps-3">
                                                        <button class="decrement-button">-</button>
                                                        <span class="item-count">0</span>
                                                        <button class="increment-button">+</button>
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

                            <div class="row">
                                <div class="col-sm-12 mb-3">
                                    <input class="form-check-input" type="checkbox" id="tnc" required>
                                    <label class="form-check-label" for="tnc">I agree to the Terms &amp; Services.</label>
                                </div>
                            </div>
                            <div class="col-sm-12 text-center mb-3">
                                <p><b>Total Amount: NGN <span id="textValue">0.00</span></b></p>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-pry">Book Service</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const incrementButtons = document.querySelectorAll('.increment-button');
    const decrementButtons = document.querySelectorAll('.decrement-button');
    const selectAmountA = document.getElementById('selectAmountA');
    const selectAmountB = document.getElementById('selectAmountB');
    const selectAmountC = document.getElementById('selectAmountC');
    const textValue = document.getElementById('textValue');

    incrementButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault(); e.stopPropagation();
            const span = btn.parentElement.querySelector('.item-count');
            span.textContent = parseInt(span.textContent) + 1;
            updateTotals();
        });
    });
    decrementButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault(); e.stopPropagation();
            const span = btn.parentElement.querySelector('.item-count');
            const cur = parseInt(span.textContent);
            if (cur > 0) { span.textContent = cur - 1; updateTotals(); }
        });
    });

    function updateTotals() {
        const adultCount  = parseInt(document.querySelector("[data-item-type='Adult'] .item-count").textContent);
        const childCount  = parseInt(document.querySelector("[data-item-type='Child'] .item-count").textContent);
        const infantCount = parseInt(document.querySelector("[data-item-type='Infant'] .item-count").textContent);
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

    if (airportVal === 'International') { airline1Div.classList.remove('d-none'); }
    else if (airportVal === 'Local')    { airline2Div.classList.remove('d-none'); }

    sel1.addEventListener('change', function () {
        other1Div.classList.toggle('d-none', this.value !== 'OTHERS');
    });
    sel2.addEventListener('change', function () {
        other2Div.classList.toggle('d-none', this.value !== 'OTHERS');
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
