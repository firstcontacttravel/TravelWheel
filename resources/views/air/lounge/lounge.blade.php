@component('layouts.app', ['title' => 'Airport Lounge - TravelWheel'])
@include('air.lounge.partials.lounge-ui')

<section class="lounge-page">
    <div class="lounge-wrap">
        <div class="lounge-hero">
            <div class="lounge-hero-main">
                <div class="lounge-kicker"><x-ph-icon name="couch" /> Airport Lounge</div>
                <h1 class="lounge-title">Airport Lounge and Services</h1>
                <p class="lounge-copy">
                    As part of our aggregating effort we also provide Lounge service, a space within the airport
                    zone where you can have a rest for a while before your flight.
                </p>

                @if(session('error'))
                    <div class="lounge-note">{{ session('error') }}</div>
                @endif

                <form action="{{ route('air.lounges') }}" method="POST" id="bookingForm" class="mt-4">
                    @csrf
                    <div class="lounge-field">
                        <label class="lounge-label" for="stateselect">State</label>
                        <select class="form-select" id="stateselect" name="state" required>
                            <option value="">-- Select State --</option>
                            <option value="Abuja">FCT - Abuja</option>
                            <option value="Lagos">Lagos - Ikeja</option>
                            <option value="Kano">Kano - Kano</option>
                        </select>
                        <small id="stateError" class="text-danger" style="display:none;">Please select a state.</small>
                    </div>

                    <div class="lounge-field lounge-hide" id="airport1">
                        <label class="lounge-label">Airports In Abuja</label>
                        <select class="form-select" id="airportSelect1" name="airports">
                            <option value="">-- Choose Airport --</option>
                            <option value="1">International Airport</option>
                            <option value="2">Local Airport</option>
                        </select>
                        <small id="airport1Error" class="text-danger" style="display:none;">Please select an airport.</small>
                    </div>

                    <div class="lounge-field lounge-hide" id="airport2">
                        <label class="lounge-label">Airport In Lagos</label>
                        <select class="form-select" id="airportSelect2" name="airports1">
                            <option value="">-- Choose Airport --</option>
                            <option value="1">International Airport</option>
                            <option value="2">Local Airport</option>
                        </select>
                        <small id="airport2Error" class="text-danger" style="display:none;">Please select an airport.</small>
                    </div>

                    <div class="lounge-field lounge-hide" id="airport3">
                        <label class="lounge-label">Airport In Kano</label>
                        <select class="form-select" id="airportSelect3" name="airports2">
                            <option value="">-- Choose Airport --</option>
                            <option value="1">International Airport</option>
                        </select>
                        <small id="airport3Error" class="text-danger" style="display:none;">Please select an airport.</small>
                    </div>

                    <div class="lounge-field lounge-hide" id="airlineDiv">
                        <label class="lounge-label">Select Airline (for terminal routing)</label>
                        <select class="form-select" id="airlineselect1" name="airline">
                            <option value="">-- Choose Airline --</option>
                            <option value="1">AIR PEACE (New Terminal)</option>
                            <option value="2">Other Airlines (Old Terminal)</option>
                        </select>
                        <small id="airlineError" class="text-danger" style="display:none;">Please select an airline.</small>
                    </div>

                    <button type="submit" class="lounge-btn w-100 mt-2">
                        Book Service <x-ph-icon name="arrow-right" />
                    </button>
                </form>
            </div>

            <div class="lounge-panel">
                <div class="lounge-panel-title"><x-ph-icon name="sparkle" /> Lounge Facilities</div>
                <p class="lounge-copy" style="margin-bottom:14px;">The list of facilities/services depends on the lounge selected.</p>
                <ul class="lounge-list">
                    <li><x-ph-icon name="couch" /> Relaxation (comfy seats).</li>
                    <li><x-ph-icon name="hamburger" /> Refreshment (free snacks).</li>
                    <li><x-ph-icon name="check-circle" /> Assorted drinks (spirits and liquors).</li>
                    <li><x-ph-icon name="hamburger" /> Food (buffet or snacks).</li>
                    <li><x-ph-icon name="wifi-high" /> Free Wi-Fi.</li>
                    <li><x-ph-icon name="newspaper" /> Flight information.</li>
                </ul>
                <div class="lounge-note">A Lounge Pass will be generated after a successful transaction. It expires after the travel date.</div>
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
        airport1.classList.add('lounge-hide');
        airport2.classList.add('lounge-hide');
        airport3.classList.add('lounge-hide');
        airlineDiv.classList.add('lounge-hide');
        if (this.value === 'Abuja') airport1.classList.remove('lounge-hide');
        else if (this.value === 'Lagos') airport2.classList.remove('lounge-hide');
        else if (this.value === 'Kano') airport3.classList.remove('lounge-hide');
    });

    airportSelect2.addEventListener('change', function () {
        airlineDiv.classList.toggle('lounge-hide', this.value !== '1');
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
