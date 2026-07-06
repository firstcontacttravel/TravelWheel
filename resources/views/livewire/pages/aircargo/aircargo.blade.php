<div>
    <link rel="stylesheet" href="{{ asset('css/visa-flow.css') }}">

    @include('air.air_cargo.partials.cargo-ui')

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
                <a href="{{ route('air.protocol') }}"><img src="{{ asset('assets/Protocol 70.png') }}" alt=""><span>Protocol</span></a>
                <a href="{{ route('air.insurance') }}"><img src="{{ asset('assets/Insurance 70.png') }}" alt=""><span>Insurance</span></a>
                <a href="{{ route('air.visa') }}"><img src="{{ asset('assets/Visa 70.png') }}" alt=""><span>Visa</span></a>
                <a class="active" href="{{ route('air.cargo') }}" aria-current="page"><img src="{{ asset('assets/Air Cargo 70.png') }}" alt=""><span>Cargo</span></a>
                <a href="{{ route('air.support') }}"><img src="{{ asset('assets/Support 70.png') }}" alt=""><span>Support</span></a>
            </nav>

            <div class="vw-card">
                <div class="vw-card__head">
                    <div>
                        <span class="vw-kicker">Air cargo</span>
                        <h2>Ship or track a shipment</h2>
                    </div>
                </div>

                <div class="cargo-vw-grid">
                    <div class="cargo-vw-panel">
                        <div class="cargo-vw-panel-title"><x-ph-icon name="briefcase" /> Create Shipment</div>
                        <label class="vw-field" for="stateselect">
                            <span>Shipment Type</span>
                            <select id="stateselect">
                                <option value="">-- Select Shipment Type --</option>
                                <option value="{{ route('air.cargo.international') }}">International Shipment</option>
                                <option value="{{ route('air.cargo.international') }}">Local Shipment</option>
                            </select>
                        </label>
                        <button type="button" id="purchaseButton" class="vw-search-btn cargo-vw-search">
                            Continue
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M5 12h14"></path>
                                <path d="m12 5 7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="cargo-vw-panel">
                        <div class="cargo-vw-panel-title"><x-ph-icon name="list-magnifying-glass" /> Track Shipment</div>
                        <label class="vw-field" for="tracking_id">
                            <span>Shipment ID</span>
                            <input type="text" id="tracking_id" placeholder="Enter Shipment ID">
                        </label>
                        <button type="button" id="trackButton" class="vw-search-btn cargo-vw-search">
                            Track Shipment
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="cargo-grid p-3">
        <div class="cargo-panel">
            <div class="cargo-panel-title"><x-ph-icon name="sparkle" /> Why Ship With Us</div>
            <ul class="cargo-list">
                <li><x-ph-icon name="check-circle" /> Fast, reliable international air freight.</li>
                <li><x-ph-icon name="check-circle" /> Document and package shipping options.</li>
                <li><x-ph-icon name="check-circle" /> Optional pickup or drop-off.</li>
                <li><x-ph-icon name="check-circle" /> Instant price estimates by destination zone.</li>
            </ul>
        </div>

        <div class="cargo-panel">
            <div class="cargo-panel-title"><x-ph-icon name="compass" /> How It Works</div>
            <div class="cargo-steps">
                <span class="cargo-step cargo-step-active"><x-ph-icon name="map-pin" /> Shipment details</span>
                <span class="cargo-step"><x-ph-icon name="receipt" /> Review &amp; price</span>
                <span class="cargo-step"><x-ph-icon name="credit-card" /> Pay</span>
                <span class="cargo-step"><x-ph-icon name="list-magnifying-glass" /> Track</span>
            </div>
            <p class="cargo-copy">After a successful payment, a shipment receipt is generated and emailed to you.</p>
            <div class="cargo-note">Prices are estimated until your shipment is re-weighed by TravelWheel.</div>
        </div>
    </div>

    <script>
        document.getElementById('purchaseButton').addEventListener('click', function () {
            const val = document.getElementById('stateselect').value;
            if (val) {
                window.location.href = val;
            } else {
                alert('Please select a shipment type');
            }
        });

        document.getElementById('trackButton').addEventListener('click', function () {
            const id = document.getElementById('tracking_id').value.trim();
            if (id) {
                window.location.href = 'https://www.dhl.com/ng-en/home/tracking.html?tracking-id=' + id;
            } else {
                alert('Please enter a valid Shipment ID.');
            }
        });
    </script>
</div>
