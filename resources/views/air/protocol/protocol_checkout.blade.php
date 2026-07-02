@component('layouts.app', ['title' => 'Protocol Checkout - TravelWheel'])
<style>
    .input-control {
        display: block; width: 100%; padding: .175rem .25rem;
        font-size: 1rem; font-weight: 400; line-height: 1.5; color: #212529;
        background-color: #fff; border-top-style: hidden; border-right-style: hidden;
        border-left-style: hidden; border-bottom: 1px solid #ced4da;
        -webkit-appearance: none; -moz-appearance: none; appearance: none;
        transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
    }
    .no-outline:focus { outline: none; }
    .hidden { display: none; }
</style>

<section class="shadow-sm py-4">
    <div class="container">
        <div class="row pt-3 pb-2">
            <div class="col-sm-8 p-3">
                <h3><img src="{{ asset('assets/img/pp.png') }}" class="me-2" style="width:30px;" alt="protocol"> Airport Protocol Service</h3>
            </div>
        </div>

        <form id="payment-form" action="{{ route('air.protocolmakePurchase') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="plan" value="{{ $dataform['plan'] ?? '' }}">
            <input type="hidden" name="nops" id="nops" value="{{ $dataform['nop'] ?? 1 }}">

            <div class="row shadow p-4 mb-5">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0" style="color:rgba(13,156,83,1);">Protocol Service Checkout</h5>
                        </div>
                        <div class="card-body">
                            {{-- Passenger forms will be duplicated here by JS --}}
                            <div class="passenger mb-2 hidden">
                                <div class="col-sm-12">
                                    <fieldset class="border p-2">
                                        <small><b>Passenger 1:</b></small>
                                        <div class="row">
                                            <div class="col-sm-4 mb-2">
                                                <label class="form-label">Firstname</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fa fa-user"></i></span>
                                                    <input type="text" class="form-control" name="firstname" required>
                                                </div>
                                            </div>
                                            <div class="col-sm-4 mb-2">
                                                <label class="form-label">Lastname</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fa fa-user"></i></span>
                                                    <input type="text" class="form-control" name="lastname" required>
                                                </div>
                                            </div>
                                            <div class="col-sm-4 mb-2">
                                                <label class="form-label">PNR / Reservation Code</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fa fa-file"></i></span>
                                                    <input type="text" class="form-control" name="pnr" required>
                                                </div>
                                            </div>
                                            <div class="col-sm-4 mb-2">
                                                <label class="form-label">e-Ticket No.</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fa fa-file"></i></span>
                                                    <input type="text" class="form-control" name="ticket-no" required>
                                                </div>
                                            </div>
                                            <div class="col-sm-4 mb-2">
                                                <label class="form-label">Number of Bags</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fa fa-briefcase"></i></span>
                                                    <input type="number" class="form-control" name="nobs" required>
                                                </div>
                                            </div>
                                            <div class="col-sm-4 mb-2">
                                                <label class="form-label">Upload Means Of ID</label>
                                                <input class="form-control" type="file" name="means-id" required>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>
                            </div>

                            <div id="duplicate-container"></div>

                            {{-- Optional request details --}}
                            @if($dataform['airport'] == 'International Airport')
                                @if(!empty($dataform['optinal_requestD']))
                                <div class="mb-3">
                                    <fieldset class="border p-2">
                                        <small><b>Pick-Up Details:</b></small>
                                        <div class="row">
                                            <div class="col-sm-6 mb-2">
                                                <label class="form-label">Pick-Up Location</label>
                                                <input type="text" class="form-control" name="pickUpAddress" required>
                                            </div>
                                            <div class="col-sm-6 mb-2">
                                                <label class="form-label">Pick-Up Vehicle</label>
                                                <input type="text" class="form-control" name="pickUpVehicle" value="{{ $optionalVehicle }}" required>
                                                <input type="hidden" name="seaters" value="{{ $seaters }}">
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>
                                @elseif(!empty($dataform['optinal_requestA']))
                                <div class="mb-3">
                                    <fieldset class="border p-2">
                                        <small><b>Drop-Off Details:</b></small>
                                        <div class="row">
                                            <div class="col-sm-6 mb-2">
                                                <label class="form-label">Drop-Off Location</label>
                                                <input type="text" class="form-control" name="dropOffAddress" required>
                                            </div>
                                            <div class="col-sm-6 mb-2">
                                                <label class="form-label">Drop-Off Vehicle</label>
                                                <input type="text" class="form-control" name="dropOffVehicle" value="{{ $optionalVehicle }}" required>
                                                <input type="hidden" name="seaters" value="{{ $seaters }}">
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>
                                @endif
                            @elseif($dataform['airport'] == 'Local Airport')
                                @if(!empty($dataform['optinal_requestD2']))
                                <div class="mb-3">
                                    <fieldset class="border p-2">
                                        <small><b>Pick-Up Details:</b></small>
                                        <div class="row">
                                            <div class="col-sm-6 mb-2">
                                                <label class="form-label">Pick-Up Location</label>
                                                <input type="text" class="form-control" name="pickUpAddress" required>
                                            </div>
                                            <div class="col-sm-6 mb-2">
                                                <label class="form-label">Pick-Up Vehicle</label>
                                                <input type="text" class="form-control" name="pickUpVehicle" value="{{ $optionalVehicle }}" required>
                                                <input type="hidden" name="seaters" value="{{ $seaters }}">
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>
                                @elseif(!empty($dataform['optinal_requestA2']))
                                <div class="mb-3">
                                    <fieldset class="border p-2">
                                        <small><b>Drop-Off Details:</b></small>
                                        <div class="col-sm-12 mb-2">
                                            <label class="form-label">Drop-Off Location</label>
                                            <input type="text" class="form-control" name="dropOffAddress" required>
                                        </div>
                                    </fieldset>
                                </div>
                                @endif
                            @endif

                            {{-- Next of Kin --}}
                            <div class="mb-3">
                                <fieldset class="border p-2">
                                    <small><b>Next of Kin Details:</b></small>
                                    <div class="row">
                                        <div class="col-sm-6 mb-2">
                                            <label class="form-label">Full Name</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fa fa-user"></i></span>
                                                <input type="text" class="form-control" name="nextOfKin_fullname" required>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 mb-2">
                                            <label class="form-label">Phone Number</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fa fa-phone"></i></span>
                                                <input type="text" class="form-control" name="nextOfKin_phone" required>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>

                            {{-- Confirm Details --}}
                            <div class="row mt-3">
                                <small class="text-muted mb-2"><b>Confirm your details</b></small>
                                <hr>
                                <div class="col-sm-4 mb-2">
                                    <label class="form-label">Location</label>
                                    <input type="hidden" name="state" value="{{ $dataform['location'] ?? '' }}">
                                    <input type="text" disabled class="input-control no-outline" value="{{ $dataform['location'] ?? '' }}">
                                </div>
                                <div class="col-sm-4 mb-2">
                                    <label class="form-label">Airport</label>
                                    <input type="hidden" name="airport" value="{{ $dataform['airport'] ?? '' }}">
                                    <input type="text" disabled class="input-control no-outline" value="{{ $dataform['airport'] ?? '' }}">
                                </div>
                                <div class="col-sm-4 mb-2">
                                    <label class="form-label">Travel Date</label>
                                    <input type="hidden" name="travel_date" value="{{ $dataform['travel_date'] ?? '' }}">
                                    <input type="text" disabled class="input-control no-outline" value="{{ $dataform['travel_date'] ?? '' }}">
                                </div>
                                <div class="col-sm-4 mb-2">
                                    <label class="form-label">{{ $dataform['service'] ?? '' }} Time</label>
                                    <input type="hidden" name="d_time" value="{{ $dataform['d_time'] ?? '' }}">
                                    <input type="text" disabled class="input-control no-outline" value="{{ $dataform['d_time'] ?? '' }}">
                                </div>
                                <div class="col-sm-4 mb-2">
                                    <label class="form-label">Airline</label>
                                    <input type="hidden" name="airline" value="{{ $dataform['airline'] ?? '' }}">
                                    <input type="text" disabled class="input-control no-outline" value="{{ $dataform['airline'] ?? '' }}">
                                </div>
                                <div class="col-sm-4 mb-2">
                                    <label class="form-label">No. of Passengers</label>
                                    <input type="hidden" name="no_of_passenger" id="nop" value="{{ $dataform['nop'] ?? '' }}">
                                    <input type="hidden" name="package" value="{{ $dataform['plan'] ?? '' }}">
                                    <input type="text" disabled class="input-control no-outline" value="{{ $dataform['nop'] ?? '' }}">
                                </div>
                                <div class="col-sm-4 mb-2">
                                    <label class="form-label">Phone Number</label>
                                    <input type="hidden" name="phone" value="{{ $dataform['phone_no'] ?? '' }}">
                                    <input type="text" disabled class="input-control no-outline" value="{{ $dataform['phone_no'] ?? '' }}">
                                </div>
                                <div class="col-sm-4 mb-2">
                                    <label class="form-label">Email</label>
                                    <input type="hidden" name="email" value="{{ $dataform['email'] ?? '' }}">
                                    <input type="text" disabled class="input-control no-outline" value="{{ $dataform['email'] ?? '' }}">
                                </div>
                                <div class="col-sm-4 mb-2">
                                    <label class="form-label">Optional Request</label>
                                    <input type="hidden" name="optional_request" value="{{ $optinal_request }}">
                                    <input type="text" disabled class="input-control no-outline" value="{{ $optinal_request }}: {{ $optionalVehicle }}">
                                </div>
                                <input type="hidden" name="service" value="{{ $dataform['service'] ?? '' }}">
                            </div>

                            <div class="col-12 p-3 text-center">
                                <a href="javascript:history.back()" class="btn btn-sm btn-secondary">Back to Edit</a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Sidebar: Price & Payment --}}
                <div class="col-sm-4">
                    <div class="card-body bg-light p-3">
                        <h5>Protocol Service Price</h5>
                        @php
                            $c_amount   = $dataform['amount'] ?? 0;
                            $amount     = (int) str_replace(',', '', $c_amount);
                            $totalPrice = $amount;
                            $percentage = 7.5;
                            $vatAmt     = ($percentage / 100) * $totalPrice;
                            $total      = $totalPrice + $vatAmt;
                        @endphp

                        <div class="row mb-2">
                            <div class="col-8"><span>Amount For Service</span></div>
                            <div class="col-4 text-end">
                                <span>₦{{ $dataform['amount'] ?? '0' }}</span>
                                <input type="hidden" name="c_amount" value="{{ $dataform['amount'] ?? '0' }}">
                            </div>
                        </div>
                        <hr>
                        <div class="row mb-2">
                            <div class="col-8"><span>Value Added Tax (VAT 7.5%)</span></div>
                            <div class="col-4 text-end">
                                <span>₦{{ number_format($vatAmt, 2) }}</span>
                                <input type="hidden" name="vat" value="{{ $vatAmt }}">
                            </div>
                        </div>
                        <hr>
                        <div class="row mb-2">
                            <div class="col-8"><b>Total Amount (incl. Tax)</b></div>
                            <div class="col-4 text-end">
                                <b>₦{{ number_format($total, 2) }}</b>
                                <input type="hidden" name="p_amount" value="{{ $total }}">
                            </div>
                        </div>
                        <hr>

                        @if(($dataform['optinal_requestD'] ?? false) || ($dataform['optinal_requestD2'] ?? false))
                            <small class="text-danger d-block mb-2">Note: Pick-Up Price Is Not Included.</small>
                        @elseif(($dataform['optinal_requestA'] ?? false) || ($dataform['optinal_requestA2'] ?? false))
                            <small class="text-danger d-block mb-2">Note: Drop-Off Price Is Not Included.</small>
                        @endif

                        <div class="d-grid mt-3">
                            <button type="submit" class="btn btn-success">Proceed to Payment</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<script>
    function duplicateFormElements() {
        const nop = parseInt(document.getElementById('nop').value);
        if (isNaN(nop) || nop <= 0) return;

        const originalCard     = document.querySelector('.passenger');
        const duplicateContainer = document.getElementById('duplicate-container');
        duplicateContainer.innerHTML = '';

        for (let i = 1; i <= nop; i++) {
            const newCard = originalCard.cloneNode(true);
            newCard.classList.remove('hidden');
            newCard.querySelector('small').textContent = `Passenger ${i}:`;
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
