<div>
    <link rel="stylesheet" href="{{ asset('css/visa-flow.css') }}">

    @include('air.insurance.partials.insurance-ui')

    <section class="vw-hero">
        <div class="vw-hero__inner">
            <h1>All Your Travel Needs In One Place</h1>
            <p class="vw-hero__subtitle">Simplifying Access To Travel.</p>

            @if(session('error'))
                <div class="vw-errors" role="alert"><strong>{{ session('error') }}</strong></div>
            @endif

            <nav class="vw-product-tabs" aria-label="TravelWheel services">
                <a href="{{ route('air.flight') }}"><img src="{{ asset('assets/Flight 70.png') }}" alt=""><span>Flights</span></a>
                <a href="{{ route('air.hotel') }}"><img src="{{ asset('assets/Hotel 70.png') }}" alt=""><span>Hotels</span></a>
                <a href="{{ route('air.lounge') }}"><img src="{{ asset('assets/Lounge 70.png') }}" alt=""><span>Lounge</span></a>
                <a href="{{ route('air.protocol') }}"><img src="{{ asset('assets/Protocol 70.png') }}" alt=""><span>Protocol</span></a>
                <a class="active" href="{{ route('air.insurance') }}" aria-current="page"><img src="{{ asset('assets/Insurance 70.png') }}" alt=""><span>Insurance</span></a>
                <a href="{{ route('air.visa') }}"><img src="{{ asset('assets/Visa 70.png') }}" alt=""><span>Visa</span></a>
                <a href="{{ route('air.cargo') }}"><img src="{{ asset('assets/Air Cargo 70.png') }}" alt=""><span>Cargo</span></a>
                <a href="{{ route('air.support') }}"><img src="{{ asset('assets/Support 70.png') }}" alt=""><span>Support</span></a>
            </nav>

            <form class="vw-card" action="{{ route('air.insurance.quote') }}" method="POST" id="insuranceForm">
                @csrf
                <div class="vw-card__head">
                    <div>
                        <span class="vw-kicker">Insurance quote</span>
                        <h2>Get your travel insurance quote</h2>
                    </div>
                </div>

                <div class="vw-fields insurance-vw-fields">
                    <label class="vw-field" for="booking_type">
                        <span>Booking Type</span>
                        <select id="booking_type" name="booking_type" required>
                            <option value="">-- Select booking type --</option>
                            <option value="1">Individual</option>
                            <option value="2">Family</option>
                        </select>
                    </label>

                    <label class="vw-field insurance-hide" id="elementToHide1" for="noc">
                        <span>No. Of Children</span>
                        <input type="number" id="noc" name="noc" value="0" min="0">
                    </label>

                    <label class="vw-field" for="purpose_of_travel">
                        <span>Purpose Of Travel</span>
                        <select id="purpose_of_travel" name="purpose_of_travel" required>
                            <option value="">-- Select purpose --</option>
                            <option value="Leisure">Leisure</option>
                            <option value="Business">Business</option>
                            <option value="School">School</option>
                        </select>
                    </label>

                    <label class="vw-field" for="dob">
                        <span>Date of Birth</span>
                        <input class="form-control" type="date" id="dob" name="dob" value="2000-02-02" required>
                    </label>

                    <label class="vw-field" for="phone_no">
                        <span>Phone Number</span>
                        <input type="text" id="phone_no" name="phone_no" placeholder="Phone number" required>
                    </label>

                    <label class="vw-field" for="email">
                        <span>Email Address</span>
                        <input type="text" id="email" name="email" placeholder="Email Address" required>
                    </label>

                    <label class="vw-field" for="select1">
                        <span>Destination Country</span>
                        <select id="select1" onchange="updateCoverLevel()" name="country" required>
                            @include('air.insurance._sanla_countries')
                        </select>
                    </label>

                    <label class="vw-field" for="begin_date">
                        <span>Cover Begins</span>
                        <input type="date" id="begin_date" name="begin_date" required>
                    </label>

                    <label class="vw-field" for="end_date">
                        <span>Cover Ends <b><span id="result" style="color:#b91c1c;"></span></b></span>
                        <input type="date" id="end_date" name="end_date" required>
                    </label>

                    <label class="vw-field" for="select2">
                        <span>Level of Cover</span>
                        <select id="select2" name="travel_plan" required>
                            <option value="">Level Of Cover</option>
                        </select>
                    </label>

                    <input type="hidden" name="nop" value="1">

                    <div class="insurance-vw-terms">
                        <input type="checkbox" id="termsCheck" required>
                        <label for="termsCheck">I agree to Terms &amp; Services.</label>
                    </div>
                </div>

                <div class="insurance-vw-actions">
                    <button type="submit" class="vw-search-btn insurance-vw-search" id="submitButton">
                        Get Quote
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </section>

    <div class="insurance-grid p-3">
        <div class="insurance-panel">
            <div class="insurance-panel-title"><x-ph-icon name="shield-check" /> Why Insure With Us</div>
            <ul class="insurance-list">
                <li><x-ph-icon name="check-circle" /> Worldwide medical and travel cover.</li>
                <li><x-ph-icon name="check-circle" /> Individual and family plan options.</li>
                <li><x-ph-icon name="check-circle" /> Fast, instant online quotes.</li>
                <li><x-ph-icon name="check-circle" /> Dedicated claims support.</li>
            </ul>
        </div>

        <div class="insurance-panel">
            <div class="insurance-panel-title"><x-ph-icon name="compass" /> How It Works</div>
            <div class="insurance-steps">
                <span class="insurance-step insurance-step-active"><x-ph-icon name="clipboard-text" /> Get quote</span>
                <span class="insurance-step"><x-ph-icon name="identification-card" /> Add details</span>
                <span class="insurance-step"><x-ph-icon name="users" /> Next of kin</span>
                <span class="insurance-step"><x-ph-icon name="credit-card" /> Pay</span>
            </div>
            <p class="insurance-copy">
                After a successful transaction, your policy is issued for the selected cover dates.
            </p>
            <div class="insurance-note">
                Your cover begins and ends on the dates you select above.
            </div>
        </div>
    </div>

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
            if (!isNaN(s) && !isNaN(e)) {
                resultSpan.textContent = Math.ceil((e - s) / (1000 * 60 * 60 * 24)) + ' Days';
            }
        }
        startInput.addEventListener('change', calculateDays);
        endInput.addEventListener('change', calculateDays);

        const bookingType = document.getElementById('booking_type');
        const childDiv    = document.getElementById('elementToHide1');
        bookingType.addEventListener('change', function () {
            childDiv.classList.toggle('insurance-hide', this.value !== '2');
        });
    </script>
</div>
