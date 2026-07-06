@component('layouts.app', ['title' => 'Protocol Checkout - TravelWheel'])
@include('air.protocol.partials.protocol-ui')

@php
    $c_amount = $dataform['amount'] ?? 0;
    $amount = (int) str_replace(',', '', $c_amount);
    $vatAmt = 7.5 / 100 * $amount;
    $total = $amount + $vatAmt;
    $passengerCount = (int) ($dataform['nop'] ?? 1);
@endphp

<section class="protocol-page">
    <div class="protocol-wrap">
        <div class="protocol-steps">
            <span class="protocol-step"><x-ph-icon name="map-pin" /> Select airport</span>
            <span class="protocol-step"><x-ph-icon name="tag" /> Choose plan</span>
            <span class="protocol-step"><x-ph-icon name="identification-card" /> Add details</span>
            <span class="protocol-step protocol-step-active"><x-ph-icon name="credit-card" /> Checkout</span>
        </div>

        <div class="protocol-hero-main mb-4">
            <div class="protocol-kicker"><x-ph-icon name="receipt" /> Review and Pay</div>
            <h1 class="protocol-title">Confirm your protocol booking</h1>
            <p class="protocol-copy">Add passenger identification details, review your trip information, and proceed to secure payment.</p>
        </div>

        <!-- Cloning template for passenger cards. Kept outside the form so its unfilled
             required fields never enter constraint validation (a display:none required
             field inside a form silently blocks submission in Chrome). -->
        <div class="passenger protocol-subpanel protocol-hide">
            <div class="protocol-section-title mb-3"><x-ph-icon name="user" /> <span>Passenger 1</span></div>
            <div class="row">
                <div class="col-sm-6 col-lg-4 protocol-field">
                    <label class="form-label">First Name</label>
                    <input type="text" class="form-control" name="firstname" required>
                </div>
                <div class="col-sm-6 col-lg-4 protocol-field">
                    <label class="form-label">Last Name</label>
                    <input type="text" class="form-control" name="lastname" required>
                </div>
                <div class="col-sm-6 col-lg-4 protocol-field">
                    <label class="form-label">PNR / Reservation Code</label>
                    <input type="text" class="form-control" name="pnr" required>
                </div>
                <div class="col-sm-6 col-lg-4 protocol-field">
                    <label class="form-label">e-Ticket No.</label>
                    <input type="text" class="form-control" name="ticket-no" required>
                </div>
                <div class="col-sm-6 col-lg-4 protocol-field">
                    <label class="form-label">Number of Bags</label>
                    <input type="number" class="form-control" name="nobs" min="0" required>
                </div>
                <div class="col-sm-6 col-lg-4 protocol-field">
                    <label class="form-label">Means of ID</label>
                    <input class="form-control" type="file" name="means-id" required>
                </div>
            </div>
        </div>

        <form id="payment-form" action="{{ route('air.protocolmakePurchase') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="plan" value="{{ $dataform['plan'] ?? '' }}">
            <input type="hidden" name="nops" id="nops" value="{{ $passengerCount }}">
            <input type="hidden" name="no_of_passenger" id="nop" value="{{ $passengerCount }}">
            <input type="hidden" name="package" value="{{ $dataform['plan'] ?? '' }}">

            <div class="protocol-checkout-grid">
                <div class="protocol-panel">
                    <div class="protocol-section-title"><x-ph-icon name="users-three" /> Passenger Details</div>

                    <div id="duplicate-container"></div>

                    @if($dataform['airport'] == 'International Airport')
                        @if(!empty($dataform['optinal_requestD']))
                            <div class="protocol-subpanel">
                                <div class="protocol-section-title"><x-ph-icon name="car" /> Pick-Up Details</div>
                                <div class="row">
                                    <div class="col-sm-6 protocol-field">
                                        <label class="form-label">Pick-Up Location</label>
                                        <input type="text" class="form-control" name="pickUpAddress" required>
                                    </div>
                                    <div class="col-sm-6 protocol-field">
                                        <label class="form-label">Pick-Up Vehicle</label>
                                        <input type="text" class="form-control" name="pickUpVehicle" value="{{ $optionalVehicle }}" required>
                                        <input type="hidden" name="seaters" value="{{ $seaters }}">
                                    </div>
                                </div>
                            </div>
                        @elseif(!empty($dataform['optinal_requestA']))
                            <div class="protocol-subpanel">
                                <div class="protocol-section-title"><x-ph-icon name="car-profile" /> Drop-Off Details</div>
                                <div class="row">
                                    <div class="col-sm-6 protocol-field">
                                        <label class="form-label">Drop-Off Location</label>
                                        <input type="text" class="form-control" name="dropOffAddress" required>
                                    </div>
                                    <div class="col-sm-6 protocol-field">
                                        <label class="form-label">Drop-Off Vehicle</label>
                                        <input type="text" class="form-control" name="dropOffVehicle" value="{{ $optionalVehicle }}" required>
                                        <input type="hidden" name="seaters" value="{{ $seaters }}">
                                    </div>
                                </div>
                            </div>
                        @endif
                    @elseif($dataform['airport'] == 'Local Airport')
                        @if(!empty($dataform['optinal_requestD2']))
                            <div class="protocol-subpanel">
                                <div class="protocol-section-title"><x-ph-icon name="car" /> Pick-Up Details</div>
                                <div class="row">
                                    <div class="col-sm-6 protocol-field">
                                        <label class="form-label">Pick-Up Location</label>
                                        <input type="text" class="form-control" name="pickUpAddress" required>
                                    </div>
                                    <div class="col-sm-6 protocol-field">
                                        <label class="form-label">Pick-Up Vehicle</label>
                                        <input type="text" class="form-control" name="pickUpVehicle" value="{{ $optionalVehicle }}" required>
                                        <input type="hidden" name="seaters" value="{{ $seaters }}">
                                    </div>
                                </div>
                            </div>
                        @elseif(!empty($dataform['optinal_requestA2']))
                            <div class="protocol-subpanel">
                                <div class="protocol-section-title"><x-ph-icon name="car-profile" /> Drop-Off Details</div>
                                <div class="protocol-field">
                                    <label class="form-label">Drop-Off Location</label>
                                    <input type="text" class="form-control" name="dropOffAddress" required>
                                </div>
                            </div>
                        @endif
                    @endif

                    <div class="protocol-subpanel">
                        <div class="protocol-section-title"><x-ph-icon name="phone-call" /> Next of Kin</div>
                        <div class="row">
                            <div class="col-sm-6 protocol-field">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" name="nextOfKin_fullname" required>
                            </div>
                            <div class="col-sm-6 protocol-field">
                                <label class="form-label">Phone Number</label>
                                <input type="text" class="form-control" name="nextOfKin_phone" required>
                            </div>
                        </div>
                    </div>

                    <div class="protocol-subpanel">
                        <div class="protocol-section-title"><x-ph-icon name="list-magnifying-glass" /> Confirm Details</div>
                        <div class="protocol-detail-grid">
                            <div class="protocol-detail"><span>Location</span><strong>{{ $dataform['location'] ?? '' }}</strong></div>
                            <div class="protocol-detail"><span>Airport</span><strong>{{ $dataform['airport'] ?? '' }}</strong></div>
                            <div class="protocol-detail"><span>Travel Date</span><strong>{{ $dataform['travel_date'] ?? '' }}</strong></div>
                            <div class="protocol-detail"><span>{{ $dataform['service'] ?? '' }} Time</span><strong>{{ $dataform['d_time'] ?? '' }}</strong></div>
                            <div class="protocol-detail"><span>Airline</span><strong>{{ $dataform['airline'] ?? '' }}</strong></div>
                            <div class="protocol-detail"><span>Passengers</span><strong>{{ $passengerCount }}</strong></div>
                            <div class="protocol-detail"><span>Phone</span><strong>{{ $dataform['phone_no'] ?? '' }}</strong></div>
                            <div class="protocol-detail"><span>Email</span><strong>{{ $dataform['email'] ?? '' }}</strong></div>
                            <div class="protocol-detail"><span>Optional Request</span><strong>{{ $optinal_request }}{{ $optionalVehicle !== 'None' ? ': '.$optionalVehicle : '' }}</strong></div>
                        </div>

                        <input type="hidden" name="state" value="{{ $dataform['location'] ?? '' }}">
                        <input type="hidden" name="airport" value="{{ $dataform['airport'] ?? '' }}">
                        <input type="hidden" name="travel_date" value="{{ $dataform['travel_date'] ?? '' }}">
                        <input type="hidden" name="d_time" value="{{ $dataform['d_time'] ?? '' }}">
                        <input type="hidden" name="airline" value="{{ $dataform['airline'] ?? '' }}">
                        <input type="hidden" name="phone" value="{{ $dataform['phone_no'] ?? '' }}">
                        <input type="hidden" name="email" value="{{ $dataform['email'] ?? '' }}">
                        <input type="hidden" name="optional_request" value="{{ $optinal_request }}">
                        <input type="hidden" name="service" value="{{ $dataform['service'] ?? '' }}">
                    </div>

                    <a href="javascript:history.back()" class="protocol-btn protocol-btn-secondary">
                        <x-ph-icon name="arrow-left" /> Back to Edit
                    </a>
                </div>

                <aside class="protocol-summary protocol-panel">
                    <div class="protocol-section-title"><x-ph-icon name="wallet" /> Payment Summary</div>
                    <div class="protocol-total-row">
                        <span>Service amount</span>
                        <strong>₦{{ $dataform['amount'] ?? '0' }}</strong>
                        <input type="hidden" name="c_amount" value="{{ $dataform['amount'] ?? '0' }}">
                    </div>
                    <div class="protocol-total-row">
                        <span>VAT 7.5%</span>
                        <strong>₦{{ number_format($vatAmt, 2) }}</strong>
                        <input type="hidden" name="vat" value="{{ $vatAmt }}">
                    </div>
                    <div class="protocol-total-row protocol-grand-total">
                        <span>Total</span>
                        <strong>₦{{ number_format($total, 2) }}</strong>
                        <input type="hidden" name="p_amount" value="{{ $total }}">
                    </div>

                    @if(($dataform['optinal_requestD'] ?? false) || ($dataform['optinal_requestD2'] ?? false))
                        <div class="protocol-note">Pick-up pricing is handled separately.</div>
                    @elseif(($dataform['optinal_requestA'] ?? false) || ($dataform['optinal_requestA2'] ?? false))
                        <div class="protocol-note">Drop-off pricing is handled separately.</div>
                    @endif

                    <button type="submit" class="protocol-btn w-100 mt-3">
                        Proceed to Payment <x-ph-icon name="arrow-right" />
                    </button>
                </aside>
            </div>
        </form>
    </div>
</section>

<script>
    function duplicateFormElements() {
        const nop = parseInt(document.getElementById('nop').value);
        if (isNaN(nop) || nop <= 0) return;

        const originalCard = document.querySelector('.passenger');
        const duplicateContainer = document.getElementById('duplicate-container');
        duplicateContainer.innerHTML = '';

        for (let i = 1; i <= nop; i++) {
            const newCard = originalCard.cloneNode(true);
            newCard.classList.remove('protocol-hide');
            const title = newCard.querySelector('.protocol-section-title span');
            if (title) title.textContent = `Passenger ${i}`;
            newCard.querySelectorAll('input').forEach(input => {
                const name = input.getAttribute('name');
                if (name) input.setAttribute('name', `${name}${i}`);
            });
            duplicateContainer.appendChild(newCard);
        }
    }

    window.addEventListener('load', duplicateFormElements);

    document.getElementById('payment-form').addEventListener('submit', function (e) {
        e.preventDefault();
        setTimeout(() => this.submit(), 300);
    });
</script>
@endcomponent
