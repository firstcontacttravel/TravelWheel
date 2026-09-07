<div>
    <link rel="stylesheet" href="{{ asset('css/visa-flow.css') }}">
    <link rel="stylesheet" href="{{ asset('css/flight-widget.css') }}">

    @include('air.lounge.partials.lounge-ui')

    <section class="vw-hero">
        <div class="vw-hero__inner">
            <h1>All Your Travel Needs In One Place</h1>
            <p class="vw-hero__subtitle">Simplifying Access To Travel.</p>

            @if(session('error'))
                <div class="vw-errors" role="alert"><strong>{{ session('error') }}</strong></div>
            @endif

            <nav class="vw-product-tabs" aria-label="TravelWheel services">
                <a href="{{ url('/') }}"><img src="{{ asset('assets/Flight 70.png') }}" alt=""><span>Flights</span></a>
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
                    <label class="vw-field" for="scopeSelect">
                        <span>Lounge location</span>
                        <select id="scopeSelect" name="scope">
                            <option value="local">Within Nigeria</option>
                            <option value="global">Outside Nigeria</option>
                        </select>
                    </label>

                    <label class="vw-field" id="localState" for="stateselect">
                        <span>State</span>
                        <select id="stateselect" name="state" required>
                            <option value="">-- Select State --</option>
                            <option value="Abuja">FCT - Abuja</option>
                            <option value="Lagos">Lagos - Ikeja</option>
                            <option value="Kano">Kano - Kano</option>
                        </select>
                    </label>

                    <label class="vw-field" id="localService" for="serviceSelect">
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

                    <label class="vw-field lounge-hide" id="globalIata" for="iataDisplay" style="position:relative;">
                        <span>Airport, city, or IATA code</span>
                        <div class="fw-input-wrap">
                            <input id="iataDisplay" type="text" placeholder="e.g. Lagos, Sydney, or LOS" autocomplete="off">
                            <div class="fw-ac-dropdown" id="iataDropdown"></div>
                        </div>
                        <input type="hidden" id="iataCode" name="iata" value="">
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
        const bookingForm    = document.getElementById('bookingForm');
        const scopeSelect    = document.getElementById('scopeSelect');
        const localState     = document.getElementById('localState');
        const localService   = document.getElementById('localService');
        const globalIata     = document.getElementById('globalIata');
        const stateselect    = document.getElementById('stateselect');
        const serviceSelect  = document.getElementById('serviceSelect');
        const iataDisplay    = document.getElementById('iataDisplay');
        const iataCode       = document.getElementById('iataCode');
        const iataDropdown   = document.getElementById('iataDropdown');
        const airport1       = document.getElementById('airport1');
        const airport2       = document.getElementById('airport2');
        const airport3       = document.getElementById('airport3');
        const airportSelect1 = document.getElementById('airportSelect1');
        const airportSelect2 = document.getElementById('airportSelect2');
        const airportSelect3 = document.getElementById('airportSelect3');

        const LOCAL_SEARCH_URL  = '{{ route('air.lounges') }}';
        const GLOBAL_SEARCH_URL = '{{ route('air.lounge.global.search') }}';

        function updateAirportVisibility() {
            airport1.classList.add('lounge-hide');
            airport2.classList.add('lounge-hide');
            airport3.classList.add('lounge-hide');
            if (stateselect.value === 'Abuja') airport1.classList.remove('lounge-hide');
            else if (stateselect.value === 'Lagos') airport2.classList.remove('lounge-hide');
            else if (stateselect.value === 'Kano') airport3.classList.remove('lounge-hide');
        }

        function applyScope() {
            const isGlobal = scopeSelect.value === 'global';

            localState.classList.toggle('lounge-hide', isGlobal);
            localService.classList.toggle('lounge-hide', isGlobal);
            globalIata.classList.toggle('lounge-hide', !isGlobal);

            stateselect.required = !isGlobal;
            serviceSelect.required = !isGlobal;
            iataDisplay.required = isGlobal;

            if (isGlobal) {
                airport1.classList.add('lounge-hide');
                airport2.classList.add('lounge-hide');
                airport3.classList.add('lounge-hide');
            } else {
                updateAirportVisibility();
            }

            bookingForm.action = isGlobal ? GLOBAL_SEARCH_URL : LOCAL_SEARCH_URL;
        }

        scopeSelect.addEventListener('change', applyScope);
        stateselect.addEventListener('change', updateAirportVisibility);

        // ── Airport/city autocomplete for the "Outside Nigeria" IATA field ──
        // Same ranked-search approach as the flight search widget: exact/prefix
        // IATA hits first, then city/name/country matches. The visible input
        // can show a city or airport name; only a picked (or exactly-typed)
        // 3-letter code ever reaches the hidden #iataCode field that's actually
        // submitted.
        let iataAirports = [];
        fetch('{{ asset('assets/data/airports.json') }}')
            .then(r => r.json())
            .then(d => { iataAirports = d; })
            .catch(e => console.error('[Lounge] airports.json:', e));

        function airportRank(a, q) {
            const iata = (a.iata || '').toLowerCase();
            const city = (a.city || '').toLowerCase();
            const name = (a.name || '').toLowerCase();
            const country = (a.country || '').toLowerCase();
            if (iata === q) return 0;
            if (iata.startsWith(q)) return 1;
            if (city.startsWith(q)) return 2;
            if (name.startsWith(q)) return 3;
            if (city.includes(q)) return 4;
            if (name.includes(q)) return 5;
            if (country.startsWith(q)) return 6;
            if (country.includes(q)) return 7;
            return 99;
        }

        function airportSearch(q) {
            q = q.toLowerCase().trim();
            if (q.length < 2) return [];
            return iataAirports
                .filter(a => airportRank(a, q) < 99)
                .sort((a, b) => airportRank(a, q) - airportRank(b, q))
                .slice(0, 8);
        }

        function selectIata(a) {
            iataDisplay.value = (a.city || a.name) + ' (' + a.iata + ')';
            iataCode.value = a.iata;
            iataDropdown.classList.remove('fw-open');
            iataDropdown.innerHTML = '';
        }

        function renderIataDropdown(results) {
            iataDropdown.innerHTML = '';

            if (results.length === 0) {
                if (iataDisplay.value.trim().length >= 2) {
                    const empty = document.createElement('div');
                    empty.className = 'fw-ac-empty';
                    empty.textContent = 'No airports found';
                    iataDropdown.appendChild(empty);
                    iataDropdown.classList.add('fw-open');
                } else {
                    iataDropdown.classList.remove('fw-open');
                }
                return;
            }

            results.forEach((a, i) => {
                const item = document.createElement('div');
                item.className = 'fw-ac-item' + (i === 0 ? ' fw-hi' : '');
                item.innerHTML = '<span class="fw-ac-iata"></span>'
                    + '<span class="fw-ac-info"><span class="fw-ac-name"></span><span class="fw-ac-city"></span></span>';
                item.querySelector('.fw-ac-iata').textContent = a.iata;
                item.querySelector('.fw-ac-name').textContent = a.name;
                item.querySelector('.fw-ac-city').textContent = [a.city, a.country].filter(Boolean).join(', ');
                item.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    selectIata(a);
                });
                iataDropdown.appendChild(item);
            });

            iataDropdown.classList.add('fw-open');
        }

        iataDisplay.addEventListener('input', function () {
            iataCode.value = '';
            renderIataDropdown(airportSearch(iataDisplay.value));
        });
        iataDisplay.addEventListener('focus', function () {
            renderIataDropdown(airportSearch(iataDisplay.value));
        });
        document.addEventListener('click', function (e) {
            if (e.target !== iataDisplay && !iataDropdown.contains(e.target)) {
                iataDropdown.classList.remove('fw-open');
            }
        });

        bookingForm.addEventListener('submit', function (e) {
            let valid = true;

            if (scopeSelect.value === 'global') {
                if (!iataCode.value && /^[A-Za-z]{3}$/.test(iataDisplay.value.trim())) {
                    iataCode.value = iataDisplay.value.trim().toUpperCase();
                }
                if (!/^[A-Z]{3}$/.test(iataCode.value)) valid = false;
            } else {
                if (!stateselect.value) valid = false;
                if (!serviceSelect.value) valid = false;
                if (stateselect.value === 'Abuja' && !airportSelect1.value) valid = false;
                if (stateselect.value === 'Lagos' && !airportSelect2.value) valid = false;
                if (stateselect.value === 'Kano' && !airportSelect3.value) valid = false;
            }

            if (!valid) e.preventDefault();
        });

        applyScope();
    </script>
</div>
