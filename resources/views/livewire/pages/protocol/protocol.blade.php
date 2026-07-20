<div>
    <link rel="stylesheet" href="{{ asset('css/visa-flow.css') }}">
    
    @include('air.protocol.partials.protocol-ui')

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
                <a href="{{ route('air.lounge') }}"><img src="{{ asset('assets/Lounge 70.png') }}" alt=""><span>Lounge</span></a>
                <a class="active" href="{{ route('air.protocol') }}" aria-current="page"><img src="{{ asset('assets/Protocol 70.png') }}" alt=""><span>Protocol</span></a>
                <a href="{{ route('air.insurance') }}"><img src="{{ asset('assets/Insurance 70.png') }}" alt=""><span>Insurance</span></a>
                <a href="{{ route('air.visa') }}"><img src="{{ asset('assets/Visa 70.png') }}" alt=""><span>Visa</span></a>
                <a href="{{ route('air.cargo') }}"><img src="{{ asset('assets/Air Cargo 70.png') }}" alt=""><span>Cargo</span></a>
                <a href="{{ route('air.support') }}"><img src="{{ asset('assets/Support 70.png') }}" alt=""><span>Support</span></a>
                <a href="{{ route('air.carhire') }}" aria-current="page"><img src="{{ asset('assets/Car Hire 70.png') }}" alt=""><span>Car Hire</span></a>

            </nav>

            <form class="vw-card" action="{{ route('air.protocolplan') }}" method="POST" id="protocolForm">
                @csrf
                <div class="vw-card__head">
                    <div>
                        <span class="vw-kicker">Protocol booking</span>
                        <h2>Book airport protocol service</h2>
                    </div>
                </div>

                <div class="vw-fields protocol-vw-fields">
                    <label class="vw-field" for="stateselect">
                        <span>Location</span>
                        <select id="stateselect" name="state" required>
                            <option value="">Select location</option>
                            <option value="Abuja">FCT - Abuja</option>
                            <option value="Lagos">Lagos - Ikeja</option>
                            <option value="Kano">Kano - Kano</option>
                            <option value="Rivers">Rivers - Port Harcourt</option>
                            <option value="Enugu">Enugu - Enugu</option>
                            <option value="Delta Asaba">Delta - Asaba</option>
                            <option value="Imo">Imo - Owerri</option>
                            <option value="Oyo">Oyo - Ibadan</option>
                            <option value="Kwara">Kwara - Ilorin</option>
                            <option value="Anambra">Anambra - Anambra</option>
                            <option value="Delta Warri">Delta - Warri</option>
                            <option value="Edo">Edo - Benin City</option>
                            <option value="Gombe">Gombe - Gombe</option>
                            <option value="Borno">Borno - Maiduguri</option>
                            <option value="Adamawa">Adamawa - Yola</option>
                            <option value="Sokoto">Sokoto - Sokoto</option>
                            <option value="Kaduna">Kaduna - Kaduna</option>
                            <option value="Cross River">Cross River - Calabar</option>
                        </select>
                    </label>

                    <input type="hidden" name="location" id="locationInput">

                    <label class="vw-field" for="airportSelect">
                        <span>Airport type</span>
                        <select name="airport" id="airportSelect" required disabled>
                            <option value="">Select location first</option>
                        </select>
                    </label>

                    <label class="vw-field" for="serviceSelect">
                        <span>Service segment</span>
                        <select name="service" id="serviceSelect" required disabled>
                            <option value="">Select segment</option>
                            <option value="Departure">Departure</option>
                            <option value="Arrival">Arrival</option>
                        </select>
                    </label>
                </div>

                <div class="protocol-vw-actions">
                    <button type="submit" class="vw-search-btn protocol-vw-search" id="submitButton" disabled>
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
    <div class="protocol-grid p-3">
        <div class="protocol-panel">
            <div class="protocol-panel-title"><x-ph-icon name="sparkle" /> Included Services</div>
            <ul class="protocol-list">
                <li><x-ph-icon name="check-circle" /> Meet and greet at the airport.</li>
                <li><x-ph-icon name="check-circle" /> Exclusive baggage handling support.</li>
                <li><x-ph-icon name="check-circle" /> Queue reduction and fast-tracking assistance.</li>
                <li><x-ph-icon name="check-circle" /> Stress-free check-in or arrival support.</li>
                <li><x-ph-icon name="check-circle" /> Coordination to pre-arranged transportation.</li>
                <li><x-ph-icon name="check-circle" /> Optional pick-up, drop-off, or police escort request.</li>
            </ul>
        </div>

        <div class="protocol-panel">
            <div class="protocol-panel-title"><x-ph-icon name="compass" /> How It Works</div>
            <div class="protocol-steps">
                <span class="protocol-step protocol-step-active"><x-ph-icon name="map-pin" /> Select airport</span>
                <span class="protocol-step"><x-ph-icon name="tag" /> Choose plan</span>
                <span class="protocol-step"><x-ph-icon name="identification-card" /> Add details</span>
                <span class="protocol-step"><x-ph-icon name="credit-card" /> Pay</span>
            </div>
            <p class="protocol-copy">
                After a successful transaction, a protocol boarding pass is generated for your travel date and service segment.
            </p>
            <div class="protocol-note">
                Your pass expires after the selected departure or arrival date.
            </div>
        </div>
    </div>
    <script>
        (() => {
            const stateselect = document.getElementById('stateselect');
            const airportSelect = document.getElementById('airportSelect');
            const serviceSelect = document.getElementById('serviceSelect');
            const locationInput = document.getElementById('locationInput');
            const submitButton = document.getElementById('submitButton');

            if (!stateselect || !airportSelect || !serviceSelect || !locationInput || !submitButton) return;

            const localOnly = ['Delta Asaba','Imo','Oyo','Kwara','Anambra','Delta Warri','Edo','Gombe','Borno','Adamawa','Sokoto','Kaduna','Cross River'];

            function syncSubmitState() {
                submitButton.disabled = !(stateselect.value && airportSelect.value && serviceSelect.value);
            }

            stateselect.addEventListener('change', function () {
                const selected = stateselect.value;
                locationInput.value = selected;
                airportSelect.innerHTML = '';
                serviceSelect.value = '';

                if (!selected) {
                    airportSelect.innerHTML = '<option value="">Select location first</option>';
                    airportSelect.disabled = true;
                    serviceSelect.disabled = true;
                    syncSubmitState();
                    return;
                }

                airportSelect.disabled = false;
                serviceSelect.disabled = false;

                if (localOnly.includes(selected)) {
                    airportSelect.innerHTML = '<option value="Local Airport" selected>Local Airport</option>';
                } else {
                    airportSelect.innerHTML = '<option value="">Select airport type</option><option value="International Airport">International Airport</option><option value="Local Airport">Local Airport</option>';
                }

                syncSubmitState();
            });

            airportSelect.addEventListener('change', syncSubmitState);
            serviceSelect.addEventListener('change', syncSubmitState);
        })();
    </script>
</div>

