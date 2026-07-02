@component('layouts.app', ['title' => 'Travel Insurance Quote - TravelWheel'])

<style>
    .hidden { display: none; }
</style>

<section class="shadow-sm">
    <div class="container">
        <div class="row p-2 pt-3">
            <div class="col-sm-12 p-3">
                <div class="row">
                    <div class="col-xs-3 col-3 col-sm-2 col-lg-1">
                        <img src="{{ asset('assets/img/ppi.png') }}" class="image-fluid w-100" alt="insurance">
                    </div>
                    <div class="col-xs-12 col-12 col-sm-10 col-lg-7">
                        <h3>Travel Insurance</h3>
                        <span class="text-muted">
                            Booking your Travel Insurance with us will give you a lot of benefits
                            as our team and partners are always ready to follow up with your claim anytime.
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row airport-form shadow p-4 mb-5">
            <div class="col-sm-6">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" style="color:rgba(13,156,83,1);">Get Quote</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('air.insurance.quote') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label class="form-label">Booking Type</label>
                                        <select class="form-select" id="booking_type" name="booking_type">
                                            <option value="">-- Select booking type --</option>
                                            <option value="1">Individual</option>
                                            <option value="2">Family</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6 hidden" id="elementToHide1">
                                    <div class="mb-3">
                                        <label class="form-label">No. Of Children</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa fa-users"></i></span>
                                            <input type="number" class="form-control" name="noc" value="0" min="0"/>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label class="form-label">Purpose Of Travel</label>
                                        <select class="form-select" name="purpose_of_travel">
                                            <option value="">-- Select purpose --</option>
                                            <option value="Leisure">Leisure</option>
                                            <option value="Business">Business</option>
                                            <option value="School">School</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label class="form-label">Date of Birth</label>
                                        <input class="form-control" type="date" name="dob" value="2000-02-02"/>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label class="form-label">Phone Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa fa-phone"></i></span>
                                            <input type="text" class="form-control" name="phone_no" placeholder="Phone number"/>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email Address</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                                            <input type="text" class="form-control" name="email" placeholder="Email Address"/>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6" id="countryDiv">
                                    <div class="mb-3">
                                        <label class="form-label">Destination Country</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa fa-flag"></i></span>
                                            <select id="select1" onchange="updateCoverLevel()" class="form-select" name="country">
                                                @include('air.insurance._sanla_countries')
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label class="form-label">Cover Begins</label>
                                        <input class="form-control" type="date" name="begin_date" id="begin_date"/>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label class="form-label">Cover Ends <b><span id="result" style="color:red;"></span></b></label>
                                        <input class="form-control" type="date" name="end_date" id="end_date"/>
                                    </div>
                                </div>
                                <div class="col-sm-6" id="coverDiv">
                                    <div class="mb-3">
                                        <label class="form-label">Level of Cover</label>
                                        <select id="select2" class="form-select" name="travel_plan">
                                            <option value="">Level Of Cover</option>
                                        </select>
                                    </div>
                                </div>
                                <input type="hidden" name="nop" value="1"/>
                                <div class="col-sm-6">
                                    <div class="mb-3 pt-4">
                                        <input class="form-check-input" type="checkbox" id="termsCheck" required/>
                                        <label class="form-check-label" for="termsCheck">I agree to Terms &amp; Services.</label>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <button type="submit" class="btn btn-pry">Get Quote</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            @if(isset($data) && isset($quote))
            <div class="col-sm-6">
                <div class="card bg-light">
                    <div class="card-body p-4">
                        <h5><b>PRICING</b></h5>
                        <p>The Quote Price Is: <strong style="font-size:20px;">₦{{ number_format($data['Amount'], 2) }}</strong></p>

                        @php
                            $travelPlanId = $data['TravelPlanId'] ?? null;
                        @endphp

                        @if($travelPlanId == 1)
                            <div>
                                <img src="{{ asset('assets/image/Benefits2.jpg') }}" class="img-fluid w-100 mb-3" alt="Benefits">
                            </div>
                        @elseif($travelPlanId == 2)
                            <div>
                                <img src="{{ asset('assets/image/Benefits1.jpg') }}" class="img-fluid w-100 mb-3" alt="Benefits">
                            </div>
                        @endif

                        <a href="{{ route('air.insurance.request') }}?qid={{ $quote->id }}" class="btn btn-success btn-lg w-100">
                            Proceed to Purchase
                        </a>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</section>

<script>
    const schengenCountries = ["2","3","6","8","9","11","12","7","13","15","19","20","36","34","35","48","59","60","69","71","84","85","10","82","5","30"];
    function updateCoverLevel() {
        const val = document.getElementById('select1').value;
        const sel2 = document.getElementById('select2');
        sel2.innerHTML = '';
        if (schengenCountries.includes(val)) {
            sel2.add(new Option('Schengen', 'Schengen'));
        } else {
            sel2.add(new Option('Worldwide Area 1&2', 'Worldwide Area 1&2'));
        }
    }
    const startInput = document.getElementById('begin_date');
    const endInput   = document.getElementById('end_date');
    const resultSpan = document.getElementById('result');
    function calculateDays() {
        const s = new Date(startInput.value), e = new Date(endInput.value);
        if (!isNaN(s) && !isNaN(e)) resultSpan.textContent = Math.ceil((e - s) / (1000 * 60 * 60 * 24)) + ' Days';
    }
    startInput.addEventListener('change', calculateDays);
    endInput.addEventListener('change', calculateDays);

    const bookingType = document.getElementById('booking_type');
    const childDiv    = document.getElementById('elementToHide1');
    bookingType.addEventListener('change', function () {
        childDiv.style.display = this.value === '2' ? 'block' : 'none';
    });
</script>

@endcomponent
