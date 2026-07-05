@component('layouts.app', ['title' => 'Insurance Purchase - TravelWheel'])
@include('air.insurance.partials.insurance-ui')

<section class="insurance-page">
    <div class="insurance-wrap">
        <div class="insurance-steps">
            <span class="insurance-step"><x-ph-icon name="clipboard-text" /> Get quote</span>
            <span class="insurance-step"><x-ph-icon name="tag" /> Your quote</span>
            <span class="insurance-step insurance-step-active"><x-ph-icon name="identification-card" /> Add details</span>
            <span class="insurance-step"><x-ph-icon name="credit-card" /> Pay</span>
        </div>

        <div class="insurance-grid">
            <div>
                <div class="insurance-hero-main">
                    <div class="insurance-kicker"><x-ph-icon name="wallet" /> Pricing</div>
                    <h1 class="insurance-title">Quote Price</h1>
                    <p class="insurance-copy" style="font-size:24px; font-weight:900; color:#102119;">
                        &#8358;{{ number_format($quote->amount, 2) }}
                    </p>
                </div>

                <div class="insurance-panel mt-3">
                    <div class="insurance-panel-title"><x-ph-icon name="info" /> Fill in your personal details</div>
                    <p class="insurance-copy">Complete the form to proceed with your insurance purchase. All fields marked required must be filled.</p>
                    <div class="insurance-note">Your policy will be issued once payment is completed.</div>
                </div>
            </div>

            <div class="insurance-panel">
                <div class="insurance-panel-title"><x-ph-icon name="identification-card" /> Customer Details</div>

                <form action="{{ route('air.insurance.purchase') }}" method="POST">
                    @csrf
                    <input type="hidden" name="bookingTypeId" value="{{ $quote->bookingTypeId }}">
                    <input type="hidden" name="qouteId" value="{{ $quote->quoteRequestId }}">
                    <input type="hidden" name="amount" value="{{ $quote->amount }}">

                    <div class="row">
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Surname</label>
                            <input type="text" class="form-control" name="surname" placeholder="Surname" required>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Middle Name</label>
                            <input type="text" class="form-control" name="middlename" placeholder="Middle name">
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="firstname" placeholder="First name" required>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender" required>
                                <option value="">-- Select Gender --</option>
                                <option value="1">Male</option>
                                <option value="2">Female</option>
                            </select>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Title</label>
                            <select class="form-select" name="title" required>
                                @include('air.insurance._title_options')
                            </select>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Date of Birth</label>
                            @php
                                try {
                                    $formattedDob = \Carbon\Carbon::parse($quote->dob)->format('Y-m-d');
                                } catch (\Exception $e) {
                                    $formattedDob = '';
                                }
                            @endphp
                            <input class="form-control" type="date" name="dob" value="{{ $formattedDob }}" required>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Email Address</label>
                            <input type="text" class="form-control" name="email" value="{{ $quote->email }}" placeholder="Email" required>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Phone Number</label>
                            <input type="text" class="form-control" name="phone_no" value="{{ $quote->phone_no }}" placeholder="Phone number" required>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">State of Residence</label>
                            <select class="form-select" name="state" required>
                                @include('air.insurance._state_options')
                            </select>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Address</label>
                            <input class="form-control" type="text" name="address" required>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Zip Code</label>
                            <input class="form-control" type="text" name="zipcode" required>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Passport No.</label>
                            <input class="form-control" type="text" name="passport_no" required>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Occupation</label>
                            <input class="form-control" type="text" name="ocupation" required>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Nationality</label>
                            <input class="form-control" type="text" name="nationalty" required>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Marital Status</label>
                            <select class="form-select" name="marital_status" required>
                                <option value="">-- Marital Status --</option>
                                <option value="1">Single</option>
                                <option value="2">Married</option>
                                <option value="3">Divorced</option>
                                <option value="4">Widowed</option>
                                <option value="5">Separated</option>
                            </select>
                        </div>
                        <div class="col-sm-12 insurance-field">
                            <div class="form-check pt-2">
                                <input class="form-check-input" type="checkbox" id="medical" name="medical_check">
                                <label class="form-check-label" for="medical">Has Pre-Existing Medical Condition?</label>
                            </div>
                            <div class="insurance-hide mt-2" id="medicalhid">
                                <label class="form-label">Details of Medical Conditions</label>
                                <input class="form-control" name="MedicalCondition" type="text" maxlength="100" placeholder="Describe condition">
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <button type="submit" class="insurance-btn w-100 mt-2">
                                Next <x-ph-icon name="arrow-right" />
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
    document.getElementById('medical').addEventListener('change', function () {
        document.getElementById('medicalhid').classList.toggle('insurance-hide', !this.checked);
    });
</script>

@endcomponent
