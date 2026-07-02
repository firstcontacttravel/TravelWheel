@component('layouts.app', ['title' => 'Airport Lounge - TravelWheel'])

<div class="container-fluid p-0">
    <img src="{{ asset('assets/image/Lounge2.jpg') }}" class="img-fluid w-100" alt="Airport Lounge" style="max-height:400px; object-fit:cover;">
</div>

<section class="shadow-sm">
    <div class="container">
        <div class="row p-2 pt-5">
            <div class="col-sm-12 p-3">
                <div class="row">
                    <div class="col-xs-3 col-3 col-sm-2 col-lg-1">
                        <img src="{{ asset('assets/img/pal.png') }}" class="img-fluid w-100" alt="lounge">
                    </div>
                    <div class="col-xs-12 col-12 col-sm-10 col-lg-7 protocol">
                        <h3>Airport Lounge and Services</h3>
                        <span class="text-muted">
                            As part of our aggregating effort we also provide Lounge service, a space
                            within the airport zone where you can have a rest for a while before your flight.
                        </span>
                    </div>
                </div>
            </div>

            <div class="row pb-5">
                <div class="col-sm-6">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0" style="color: rgba(13, 156, 83, 1);">Select Airport</h5>
                        </div>
                        <div class="card-body">
                            @if(session('error'))
                                <div class="alert alert-danger">{{ session('error') }}</div>
                            @endif
                            <form action="{{ route('air.lounges') }}" method="POST" id="bookingForm">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label" for="stateselect">State</label>
                                    <select class="form-select" id="stateselect" name="state" required>
                                        <option value="">-- Select State --</option>
                                        <option value="Abuja">FCT - Abuja</option>
                                        <option value="Lagos">Lagos - Ikeja</option>
                                        <option value="Kano">Kano - Kano</option>
                                    </select>
                                    <span id="stateError" class="text-danger" style="display:none;">Please select a state.</span>
                                </div>
                                <div class="mb-3 d-none" id="airport1">
                                    <label class="form-label">Airports In Abuja</label>
                                    <select class="form-select" id="airportSelect1" name="airports">
                                        <option value="">-- Choose Airport --</option>
                                        <option value="1">International Airport</option>
                                        <option value="2">Local Airport</option>
                                    </select>
                                    <span id="airport1Error" class="text-danger" style="display:none;">Please select an airport.</span>
                                </div>
                                <div class="mb-3 d-none" id="airport2">
                                    <label class="form-label">Airport In Lagos</label>
                                    <select class="form-select" id="airportSelect2" name="airports1">
                                        <option value="">-- Choose Airport --</option>
                                        <option value="1">International Airport</option>
                                        <option value="2">Local Airport</option>
                                    </select>
                                    <span id="airport2Error" class="text-danger" style="display:none;">Please select an airport.</span>
                                </div>
                                <div class="mb-3 d-none" id="airport3">
                                    <label class="form-label">Airport In Kano</label>
                                    <select class="form-select" id="airportSelect3" name="airports2">
                                        <option value="">-- Choose Airport --</option>
                                        <option value="1">International Airport</option>
                                    </select>
                                    <span id="airport3Error" class="text-danger" style="display:none;">Please select an airport.</span>
                                </div>
                                <div class="mb-3 d-none" id="airlineDiv">
                                    <label class="form-label">Select Airline (for terminal routing)</label>
                                    <select class="form-select" id="airlineselect1" name="airline">
                                        <option value="">-- Choose Airline --</option>
                                        <option value="1">AIR PEACE (New Terminal)</option>
                                        <option value="2">Other Airlines (Old Terminal)</option>
                                    </select>
                                    <span id="airlineError" class="text-danger" style="display:none;">Please select an airline.</span>
                                </div>
                                <div class="col-sm-12 text-center">
                                    <button type="submit" class="btn btn-pry">Book Service</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 pb-3">
                    <div class="bg-light p-3 shadow-sm protocol">
                        <p style="color: rgba(13, 156, 83, 1);"><b>The list of our Lounge Facilities/Services depend on the lounge selected.</b></p>
                        <ul class="list-unstyled">
                            <li><i class="fa fa-check"></i> Relaxation (comfy seats).</li>
                            <li><i class="fa fa-check"></i> Refreshment (free snacks).</li>
                            <li><i class="fa fa-check"></i> Assorted Drinks (spirits and liquors)</li>
                            <li><i class="fa fa-check"></i> Food (buffet or snacks).</li>
                            <li><i class="fa fa-check"></i> Free Wi-Fi.</li>
                            <li><i class="fa fa-check"></i> Air Condition.</li>
                            <li><i class="fa fa-check"></i> TV.</li>
                            <li><i class="fa fa-check"></i> Shower.</li>
                            <li><i class="fa fa-check"></i> Flight Information.</li>
                        </ul>
                        <h6>NB: <span><b style="color:red;">A Lounge Pass will be generated, after a Successful transaction. It expires after travel date.</b></span></h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    const stateselect   = document.getElementById('stateselect');
    const airport1      = document.getElementById('airport1');
    const airport2      = document.getElementById('airport2');
    const airport3      = document.getElementById('airport3');
    const airportSelect2 = document.getElementById('airportSelect2');
    const airlineDiv    = document.getElementById('airlineDiv');

    stateselect.addEventListener('change', function () {
        airport1.classList.add('d-none');
        airport2.classList.add('d-none');
        airport3.classList.add('d-none');
        airlineDiv.classList.add('d-none');
        if (this.value === 'Abuja') airport1.classList.remove('d-none');
        else if (this.value === 'Lagos') airport2.classList.remove('d-none');
        else if (this.value === 'Kano') airport3.classList.remove('d-none');
    });

    airportSelect2.addEventListener('change', function () {
        airlineDiv.classList.toggle('d-none', this.value !== '1');
    });

    document.getElementById('bookingForm').addEventListener('submit', function (e) {
        let valid = true;
        if (!stateselect.value) { document.getElementById('stateError').style.display = 'inline'; valid = false; }
        else document.getElementById('stateError').style.display = 'none';

        if (stateselect.value === 'Abuja' && !document.getElementById('airportSelect1').value) {
            document.getElementById('airport1Error').style.display = 'inline'; valid = false;
        } else document.getElementById('airport1Error').style.display = 'none';

        if (stateselect.value === 'Lagos' && !airportSelect2.value) {
            document.getElementById('airport2Error').style.display = 'inline'; valid = false;
        } else document.getElementById('airport2Error').style.display = 'none';

        if (stateselect.value === 'Kano' && !document.getElementById('airportSelect3').value) {
            document.getElementById('airport3Error').style.display = 'inline'; valid = false;
        } else document.getElementById('airport3Error').style.display = 'none';

        if (stateselect.value === 'Lagos' && airportSelect2.value === '1' && !document.getElementById('airlineselect1').value) {
            document.getElementById('airlineError').style.display = 'inline'; valid = false;
        } else document.getElementById('airlineError').style.display = 'none';

        if (!valid) e.preventDefault();
    });
</script>

@endcomponent
