@component('layouts.app', ['title' => 'Leadway Insurance Plan - TravelWheel'])

<section class="shadow-sm">
    <div class="container">
        <div class="row p-2 pt-3">
            <div class="col-sm-12 p-3">
                <div class="row">
                    <div class="col-xs-3 col-3 col-sm-2 col-lg-1">
                        <img src="{{ asset('assets/image/leadway.png') }}" class="image-fluid w-100" alt="Leadway">
                    </div>
                    <div class="col-xs-12 col-12 col-sm-10 col-lg-7">
                        <h3>{{ $prodName }}</h3>
                        <span class="text-muted">Fill in your details to get a quote.</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row airport-form shadow p-4 mb-5">
            <div class="col-sm-12">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" style="color:rgba(13,156,83,1);">{{ $prodName }}</h5>
                        <small class="text-muted">Leadway Travel Insurance</small>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('air.insuranceLeadwayQ') }}" method="POST">
                            @csrf
                            <input type="hidden" name="prodCode" value="{{ $prodCode }}"/>
                            <input type="hidden" name="prod_code" id="prod_code" value="{{ $prodCode }}"/>
                            <input type="hidden" name="nop" value="1"/>

                            <div class="row">
                                <div class="col-sm-3 mb-3">
                                    <label class="form-label">Country</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-flag"></i></span>
                                        <select class="form-select" name="country" required>
                                            <option value="">-- Select Country --</option>
                                            @foreach($data['ctry'] as $country)
                                                <option value="{{ $country['countryName'] }}">{{ $country['countryName'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-sm-3 mb-3">
                                    <label class="form-label">No. Of Passengers</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-users"></i></span>
                                        <input type="text" class="form-control" value="1 Person Per Policy" readonly/>
                                    </div>
                                </div>

                                <div class="col-sm-3 mb-3">
                                    <label class="form-label">Cover Begins</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                                        <input class="form-control" type="date" name="begin_date" id="begin_date" required/>
                                    </div>
                                </div>

                                <div class="col-sm-3 mb-3">
                                    <label class="form-label">Cover Ends <b><span id="result" style="color:inherit;"></span></b></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                                        <input class="form-control" type="date" name="end_date" id="end_date" required/>
                                    </div>
                                    <small id="warning-msg" class="text-danger"></small>
                                </div>

                                <div class="col-sm-3 mb-3">
                                    <label class="form-label">Surname</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-user"></i></span>
                                        <input class="form-control" type="text" name="surname" placeholder="Surname" required/>
                                    </div>
                                </div>

                                <div class="col-sm-3 mb-3">
                                    <label class="form-label">First Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-user"></i></span>
                                        <input class="form-control" type="text" name="othername" placeholder="First Name" required/>
                                    </div>
                                </div>

                                <div class="col-sm-3 mb-3">
                                    <label class="form-label">Other Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-user"></i></span>
                                        <input class="form-control" type="text" name="lastname" placeholder="Other Name" required/>
                                    </div>
                                </div>

                                <div class="col-sm-3 mb-3">
                                    <label class="form-label">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                                        <input type="text" class="form-control" name="email" placeholder="Email Address" required/>
                                    </div>
                                </div>

                                <div class="col-sm-3 mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-phone"></i></span>
                                        <input type="text" class="form-control" name="phone_no" placeholder="Phone number" required/>
                                    </div>
                                </div>

                                <div class="col-sm-3 mb-3">
                                    <label class="form-label">Gender</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-user"></i></span>
                                        <select class="form-select" name="gender" required>
                                            <option value="">-- Select Gender --</option>
                                            <option value="M">Male</option>
                                            <option value="F">Female</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-sm-3 mb-3">
                                    <label class="form-label">Date of Birth</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                                        <input class="form-control" type="date" name="dob" value="2000-02-02" required/>
                                    </div>
                                </div>

                                <div class="col-sm-3 mb-3">
                                    <label class="form-label">Postal Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-map-marker"></i></span>
                                        <input type="text" class="form-control" name="address" placeholder="Your Address" required/>
                                    </div>
                                </div>

                                <div class="col-sm-3 mb-3">
                                    <label class="form-label">City</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-map-marker"></i></span>
                                        <input type="text" class="form-control" name="city" placeholder="City" required/>
                                    </div>
                                </div>

                                <div class="col-sm-3 mb-3">
                                    <label class="form-label">State</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-map-marker"></i></span>
                                        <input type="text" class="form-control" name="state" placeholder="State" required/>
                                    </div>
                                </div>

                                <div class="col-sm-3 mb-3">
                                    <label class="form-label">Passport No.</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-globe"></i></span>
                                        <input type="text" class="form-control" name="passport_no" placeholder="Passport No." required/>
                                    </div>
                                </div>

                                <div class="col-sm-3 mb-3 pt-4">
                                    <input class="form-check-input" type="checkbox" id="termsCheck" required/>
                                    <label class="form-check-label" for="termsCheck">I agree to Terms &amp; Services.</label>
                                </div>

                                <div class="col-sm-12 mt-2">
                                    <div class="row">
                                        <div class="col-6">
                                            <a href="javascript:history.back()" class="btn btn-secondary btn-sm">Back</a>
                                        </div>
                                        <div class="col-6 text-end">
                                            <button type="submit" class="btn btn-pry" id="submitBtn" disabled>Proceed</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    const prodCodeVal = document.getElementById('prod_code').value.trim().toUpperCase();
    const startInput  = document.getElementById('begin_date');
    const endInput    = document.getElementById('end_date');
    const resultSpan  = document.getElementById('result');
    const warningText = document.getElementById('warning-msg');
    const submitBtn   = document.getElementById('submitBtn');

    const rangeMap = {
        IPUM: [[11, 15], [22, 30]],
        IPHA: [[11, 15], [22, 30]],
        IPEX: [[11, 15], [22, 30]],
        IPPP: [[11, 15], [22, 30]],
        IPPB: [[11, 15], [22, 30]],
        IPSI: [[91, 180]],
        IPSE: [[91, 180]]
    };

    startInput.addEventListener('change', checkRange);
    endInput.addEventListener('change', checkRange);

    function checkRange() {
        const start = new Date(startInput.value);
        const end   = new Date(endInput.value);
        if (isNaN(start) || isNaN(end)) { clearFeedback(); return; }

        const days    = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
        resultSpan.textContent = days + ' day' + (days === 1 ? '' : 's');

        const ranges    = rangeMap[prodCodeVal];
        const isAllowed = !ranges || ranges.some(([min, max]) => days >= min && days <= max);

        if (isAllowed) {
            setValid();
        } else {
            const niceRanges = ranges.map(([min, max]) => `${min}–${max} days`).join(' or ');
            setInvalid('Coverage must be ' + niceRanges + '.');
        }
    }

    function clearFeedback() {
        resultSpan.textContent  = '';
        warningText.textContent = '';
        submitBtn.disabled      = true;
    }
    function setValid() {
        warningText.textContent = '';
        resultSpan.style.color  = 'green';
        submitBtn.disabled      = false;
    }
    function setInvalid(msg) {
        warningText.textContent = msg;
        resultSpan.style.color  = 'red';
        submitBtn.disabled      = true;
    }

    checkRange();
</script>

@endcomponent
