@component('layouts.app', ['title' => 'Family Insurance Purchase - TravelWheel'])
@include('air.insurance.partials.insurance-ui')

<section class="insurance-page">
    <div class="insurance-wrap">
        <div class="insurance-steps">
            <span class="insurance-step"><x-ph-icon name="clipboard-text" /> Get quote</span>
            <span class="insurance-step"><x-ph-icon name="tag" /> Your quote</span>
            <span class="insurance-step insurance-step-active"><x-ph-icon name="identification-card" /> Add details</span>
            <span class="insurance-step"><x-ph-icon name="credit-card" /> Pay</span>
        </div>

        <div class="insurance-hero-main mb-4">
            <div class="insurance-kicker"><x-ph-icon name="users" /> Family Plan</div>
            <h1 class="insurance-title">Fill in details for both parents</h1>
            <p class="insurance-copy" style="font-size:20px; font-weight:900; color:#102119;">
                Quote Price: &#8358;{{ number_format($quote->amount, 2) }}
            </p>
        </div>

        <form action="{{ route('air.insurance.purchase') }}" method="POST">
            @csrf
            <input type="hidden" name="noc" id="numberOfDuplicates" value="{{ $quote->noOfChildren }}">
            <input type="hidden" name="bookingTypeId" value="{{ $quote->bookingTypeId }}">
            <input type="hidden" name="qouteId" value="{{ $quote->quoteRequestId }}">
            <input type="hidden" name="amount" value="{{ $quote->amount }}">

            @php
                try {
                    $formattedDob = \Carbon\Carbon::parse($quote->dob)->format('Y-m-d');
                } catch (\Exception $e) {
                    $formattedDob = '';
                }
            @endphp

            <div class="insurance-plan-grid">
                <div class="insurance-panel">
                    <div class="insurance-panel-title"><x-ph-icon name="user" /> Parent 1</div>
                    <div class="row">
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Middle Name</label>
                            <input type="text" class="form-control" name="middlename1" placeholder="Middle name">
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="firstname" placeholder="First name" required>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender1" required>
                                <option value="">-- Gender --</option>
                                <option value="1">Male</option>
                                <option value="2">Female</option>
                            </select>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Title</label>
                            <select class="form-select" name="title1" required>
                                @include('air.insurance._title_options')
                            </select>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Date of Birth</label>
                            <input class="form-control" type="date" name="dob1" value="{{ $formattedDob }}" required>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Passport No.</label>
                            <input class="form-control" type="text" name="passport_no">
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Email Address</label>
                            <input type="text" class="form-control" name="email" value="{{ $quote->email }}" required>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Phone Number</label>
                            <input type="text" class="form-control" name="phone_no" value="{{ $quote->phone_no }}" required>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Occupation</label>
                            <input class="form-control" type="text" name="ocupation1" required>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Marital Status</label>
                            <select class="form-select" name="marital_status1" required>
                                <option value="">-- Status --</option>
                                <option value="1">Single</option>
                                <option value="2">Married</option>
                                <option value="3">Divorced</option>
                                <option value="4">Widowed</option>
                                <option value="5">Separated</option>
                            </select>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Nationality</label>
                            <input class="form-control" type="text" name="nationalty1" required>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Pre-Existing Medical Conditions</label>
                            <input class="form-control" name="MedicalCondition1" type="text" maxlength="100" placeholder="None if N/A">
                        </div>
                    </div>
                </div>

                <div class="insurance-panel">
                    <div class="insurance-panel-title"><x-ph-icon name="user" /> Parent 2</div>
                    <div class="row">
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Middle Name</label>
                            <input type="text" class="form-control" name="middlename2" placeholder="Middle name">
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="firstname2" placeholder="First name" required>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender2" required>
                                <option value="">-- Gender --</option>
                                <option value="1">Male</option>
                                <option value="2">Female</option>
                            </select>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Title</label>
                            <select class="form-select" name="title2" required>
                                @include('air.insurance._title_options')
                            </select>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Date of Birth</label>
                            <input class="form-control" type="date" name="dob2" value="{{ $formattedDob }}" required>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Passport No.</label>
                            <input class="form-control" type="text" name="passport_no2">
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Email Address</label>
                            <input type="text" class="form-control" name="email2" value="{{ $quote->email }}" required>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Phone Number</label>
                            <input type="text" class="form-control" name="phone_no2" value="{{ $quote->phone_no }}" required>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Occupation</label>
                            <input class="form-control" type="text" name="ocupation2" required>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Nationality</label>
                            <input class="form-control" type="text" name="nationalty2" required>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Pre-Existing Medical Conditions</label>
                            <input class="form-control" name="MedicalCondition2" type="text" maxlength="100" placeholder="None if N/A">
                        </div>
                    </div>
                </div>
            </div>

            <div class="insurance-panel mt-3">
                <div class="insurance-panel-title"><x-ph-icon name="house" /> General Information</div>
                <div class="row">
                    <div class="col-sm-3 insurance-field">
                        <label class="form-label">Surname (Family)</label>
                        <input type="text" class="form-control" name="surname" placeholder="Family surname" required>
                    </div>
                    <div class="col-sm-3 insurance-field">
                        <label class="form-label">State</label>
                        <select class="form-select" name="state" required>
                            @include('air.insurance._state_options')
                        </select>
                    </div>
                    <div class="col-sm-3 insurance-field">
                        <label class="form-label">Address</label>
                        <input class="form-control" type="text" name="address" required>
                    </div>
                    <div class="col-sm-3 insurance-field">
                        <label class="form-label">Zip Code</label>
                        <input class="form-control" type="text" name="zipcode" required>
                    </div>
                </div>
            </div>

            {{-- Children section (JS duplicated) --}}
            <div class="d-none">
                <div class="insurance-panel child mt-3">
                    <div class="insurance-panel-title"><x-ph-icon name="baby" /> Child 1</div>
                    <div class="row">
                        <div class="col-sm-3 insurance-field">
                            <label class="form-label">Middle Name</label>
                            <input type="text" class="form-control" name="middlenameC">
                        </div>
                        <div class="col-sm-3 insurance-field">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="firstnameC">
                        </div>
                        <div class="col-sm-3 insurance-field">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="genderC">
                                <option value="">-- Gender --</option>
                                <option value="1">Male</option>
                                <option value="2">Female</option>
                            </select>
                        </div>
                        <div class="col-sm-3 insurance-field">
                            <label class="form-label">Title</label>
                            <select class="form-select" name="titleC">
                                @include('air.insurance._title_options')
                            </select>
                        </div>
                        <div class="col-sm-3 insurance-field">
                            <label class="form-label">Date of Birth</label>
                            <input class="form-control" type="date" name="dobC">
                        </div>
                        <div class="col-sm-3 insurance-field">
                            <label class="form-label">Passport No.</label>
                            <input class="form-control" type="text" name="passport_noC">
                        </div>
                        <div class="col-sm-3 insurance-field">
                            <label class="form-label">Nationality</label>
                            <input class="form-control" type="text" name="nationaltyC">
                        </div>
                        <div class="col-sm-3 insurance-field">
                            <label class="form-label">Medical Conditions</label>
                            <input class="form-control" name="MedicalConditionC" type="text" maxlength="100" placeholder="None if N/A">
                        </div>
                    </div>
                </div>
            </div>
            <div id="duplicate-container"></div>

            <div class="text-end mt-3">
                <button type="submit" class="insurance-btn">
                    Next <x-ph-icon name="arrow-right" />
                </button>
            </div>
        </form>
    </div>
</section>

<script>
    function duplicateFormElements() {
        const n = parseInt(document.getElementById('numberOfDuplicates').value);
        if (isNaN(n) || n <= 0) return;

        const original  = document.querySelector('.child');
        const container = document.getElementById('duplicate-container');
        container.innerHTML = '';

        for (let i = 1; i <= n; i++) {
            const card = original.cloneNode(true);
            card.querySelector('.insurance-panel-title').lastChild.textContent = ' Child ' + i;
            card.querySelectorAll('input').forEach(el => el.setAttribute('name', el.getAttribute('name') + i));
            card.querySelectorAll('select').forEach(el => el.setAttribute('name', el.getAttribute('name') + i));
            container.appendChild(card);
        }
    }
    window.addEventListener('load', duplicateFormElements);
</script>

@endcomponent
