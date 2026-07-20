<div>
    <link rel="stylesheet" href="{{ asset('css/visa-flow.css') }}">

    @include('air.lounge.partials.lounge-ui')

    <section class="vw-hero">
        <div class="vw-hero__inner">
            <h1>All Your Travel Needs In One Place</h1>
            <p class="vw-hero__subtitle">Simplifying Access To Travel.</p>

            @if(session('error'))
                <div class="vw-errors" role="alert"><strong>{{ session('error') }}</strong></div>
            @endif

            <nav class="vw-product-tabs" aria-label="TravelWheel services">
                <a href="{{ route('air.flight') }}"><img src="{{ asset('assets/Flight 70.png') }}" alt=""><span>Flights</span></a>
                <!-- <a href="{{ route('air.hotel') }}"><img src="{{ asset('assets/Hotel 70.png') }}" alt=""><span>Hotels</span></a> -->
                <a class="active" href="{{ route('air.lounge') }}" aria-current="page"><img src="{{ asset('assets/Lounge 70.png') }}" alt=""><span>Lounge</span></a>
                <a href="{{ route('air.protocol') }}"><img src="{{ asset('assets/Protocol 70.png') }}" alt=""><span>Protocol</span></a>
                <a href="{{ route('air.insurance') }}"><img src="{{ asset('assets/Insurance 70.png') }}" alt=""><span>Insurance</span></a>
                <a href="{{ route('air.visa') }}"><img src="{{ asset('assets/Visa 70.png') }}" alt=""><span>Visa</span></a>
                <a href="{{ route('air.cargo') }}"><img src="{{ asset('assets/Air Cargo 70.png') }}" alt=""><span>Cargo</span></a>
                <a href="{{ route('air.support') }}"><img src="{{ asset('assets/Support 70.png') }}" alt=""><span>Support</span></a>
                <a href="{{ route('air.carhire') }}" aria-current="page"><img src="{{ asset('assets/Car Hire 70.png') }}" alt=""><span>Car Hire</span></a>

            </nav>

            <form class="vw-card" action="{{ route('air.lounges') }}" method="POST" id="bookingForm">
                @csrf
                <div class="vw-card__head">
                    <div>
                        <span class="vw-kicker">Lounge booking</span>
                        <h2>Book an airport lounge</h2>
                    </div>
                </div>

                <div class="vw-fields lounge-vw-fields">
                    <label class="vw-field" for="stateselect">
                        <span>State</span>
                        <select id="stateselect" name="state" required>
                            <option value="">-- Select State --</option>
                            <option value="Abuja">FCT - Abuja</option>
                            <option value="Lagos">Lagos - Ikeja</option>
                            <option value="Kano">Kano - Kano</option>
                        </select>
                    </label>

                    <label class="vw-field" for="serviceSelect">
                        <span>Service segment</span>
                        <select id="serviceSelect" name="service" required>
                            <option value="">-- Select segment --</option>
                            <option value="Departure">Departure</option>
                            <option value="Arrival">Arrival</option>
                        </select>
                    </label>

                    <label class="vw-field lounge-hide" id="airport1" for="airportSelect1">
                        <span>Airports In Abuja</span>
                        <select class="form-select" id="airportSelect1" name="airports">
                            <option value="">-- Choose Airport --</option>
                            <option value="1">International Airport</option>
                            <option value="2">Local Airport</option>
                        </select>
                    </label>

                    <label class="vw-field lounge-hide" id="airport2" for="airportSelect2">
                        <span>Airport In Lagos</span>
                        <select class="form-select" id="airportSelect2" name="airports1">
                            <option value="">-- Choose Airport --</option>
                            <option value="1">International Airport</option>
                            <option value="2">Local Airport</option>
                        </select>
                    </label>

                    <label class="vw-field lounge-hide" id="airport3" for="airportSelect3">
                        <span>Airport In Kano</span>
                        <select class="form-select" id="airportSelect3" name="airports2">
                            <option value="">-- Choose Airport --</option>
                            <option value="1">International Airport</option>
                        </select>
                    </label>
                </div>

                <div class="lounge-vw-actions">
                    <button type="submit" class="vw-search-btn lounge-vw-search" id="submitButton">
                        Search
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </section>

    <div class="lounge-grid p-3">
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
        </div>

        <div class="lounge-panel">
            <div class="lounge-panel-title"><x-ph-icon name="compass" /> How It Works</div>
            <div class="lounge-steps">
                <span class="lounge-step lounge-step-active"><x-ph-icon name="map-pin" /> Select airport</span>
                <span class="lounge-step"><x-ph-icon name="tag" /> Choose lounge</span>
                <span class="lounge-step"><x-ph-icon name="identification-card" /> Add details</span>
                <span class="lounge-step"><x-ph-icon name="credit-card" /> Pay</span>
            </div>
            <p class="lounge-copy">
                After a successful transaction, a lounge pass is generated for your travel date.
            </p>
            <div class="lounge-note">A Lounge Pass will be generated after a successful transaction. It expires after the travel date.</div>
        </div>
    </div>

    <script>
        const stateselect   = document.getElementById('stateselect');
        const airport1      = document.getElementById('airport1');
        const airport2      = document.getElementById('airport2');
        const airport3      = document.getElementById('airport3');
        const airportSelect2 = document.getElementById('airportSelect2');

        stateselect.addEventListener('change', function () {
            airport1.classList.add('lounge-hide');
            airport2.classList.add('lounge-hide');
            airport3.classList.add('lounge-hide');
            if (this.value === 'Abuja') airport1.classList.remove('lounge-hide');
            else if (this.value === 'Lagos') airport2.classList.remove('lounge-hide');
            else if (this.value === 'Kano') airport3.classList.remove('lounge-hide');
        });

        document.getElementById('bookingForm').addEventListener('submit', function (e) {
            let valid = true;
            if (!stateselect.value) valid = false;
            if (!document.getElementById('serviceSelect').value) valid = false;

            if (stateselect.value === 'Abuja' && !document.getElementById('airportSelect1').value) valid = false;
            if (stateselect.value === 'Lagos' && !airportSelect2.value) valid = false;
            if (stateselect.value === 'Kano' && !document.getElementById('airportSelect3').value) valid = false;

            if (!valid) e.preventDefault();
        });
    </script>
</div>
