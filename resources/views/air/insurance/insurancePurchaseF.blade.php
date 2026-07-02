@component('layouts.app', ['title' => 'Family Insurance Purchase - TravelWheel'])

<section class="shadow-sm">
    <div class="container">
        <div class="row p-2 pt-3">
            <div class="col-sm-12 p-3">
                <div class="row">
                    <div class="col-xs-3 col-3 col-sm-2 col-lg-1">
                        <img src="{{ asset('assets/img/ppi.png') }}" class="image-fluid w-100" alt="insurance">
                    </div>
                    <div class="col-xs-12 col-12 col-sm-10 col-lg-7">
                        <h3>Travel Insurance</h3>
                        <span class="text-muted">Family Plan — Fill in details for both parents.</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row airport-form shadow p-4 mb-5">
            <div class="col-sm-12 mb-3">
                <div class="bg-light p-3" style="border-radius:10px;">
                    <h5 style="color:rgba(13,156,83,1);">Insurance Family Purchase</h5>
                    <p><b>Quote Price:</b> <span style="font-size:20px; font-weight:bolder;">₦{{ number_format($quote->amount, 2) }}</span></p>
                </div>
            </div>

            <form action="{{ route('air.insurance.purchase') }}" method="POST">
                @csrf
                <input type="hidden" name="noc" id="numberOfDuplicates" value="{{ $quote->noOfChildren }}"/>
                <input type="hidden" name="bookingTypeId" value="{{ $quote->bookingTypeId }}"/>
                <input type="hidden" name="qouteId" value="{{ $quote->quoteRequestId }}"/>
                <input type="hidden" name="amount" value="{{ $quote->amount }}"/>

                @php
                    try {
                        $formattedDob = \Carbon\Carbon::parse($quote->dob)->format('Y-m-d');
                    } catch (\Exception $e) {
                        $formattedDob = '';
                    }
                @endphp

                <div class="row">
                    {{-- Parent 1 --}}
                    <div class="col-sm-6">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0" style="color:rgba(13,156,83,1);">Parent 1</h6>
                                <small class="text-muted">Fill in Details</small>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-sm-6 mb-3">
                                        <label class="form-label">Middle Name</label>
                                        <input type="text" class="form-control" name="middlename1" placeholder="Middle name"/>
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" name="firstname" placeholder="First name" required/>
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label class="form-label">Gender</label>
                                        <select class="form-select" name="gender1" required>
                                            <option value="">-- Gender --</option>
                                            <option value="1">Male</option>
                                            <option value="2">Female</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label class="form-label">Title</label>
                                        <select class="form-select" name="title1" required>
                                            @include('air.insurance._title_options')
                                        </select>
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label class="form-label">Date of Birth</label>
                                        <input class="form-control" type="date" name="dob1" value="{{ $formattedDob }}" required/>
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label class="form-label">Passport No.</label>
                                        <input class="form-control" type="text" name="passport_no"/>
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="text" class="form-control" name="email" value="{{ $quote->email }}" required/>
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label class="form-label">Phone Number</label>
                                        <input type="text" class="form-control" name="phone_no" value="{{ $quote->phone_no }}" required/>
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label class="form-label">Occupation</label>
                                        <input class="form-control" type="text" name="ocupation1" required/>
                                    </div>
                                    <div class="col-sm-6 mb-3">
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
                                    <div class="col-sm-6 mb-3">
                                        <label class="form-label">Nationality</label>
                                        <input class="form-control" type="text" name="nationalty1" required/>
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label class="form-label">Pre-Existing Medical Conditions</label>
                                        <input class="form-control" name="MedicalCondition1" type="text" maxlength="100" placeholder="None if N/A"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Parent 2 --}}
                    <div class="col-sm-6">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0" style="color:rgba(13,156,83,1);">Parent 2</h6>
                                <small class="text-muted">Fill in Details</small>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-sm-6 mb-3">
                                        <label class="form-label">Middle Name</label>
                                        <input type="text" class="form-control" name="middlename2" placeholder="Middle name"/>
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" name="firstname2" placeholder="First name" required/>
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label class="form-label">Gender</label>
                                        <select class="form-select" name="gender2" required>
                                            <option value="">-- Gender --</option>
                                            <option value="1">Male</option>
                                            <option value="2">Female</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label class="form-label">Title</label>
                                        <select class="form-select" name="title2" required>
                                            @include('air.insurance._title_options')
                                        </select>
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label class="form-label">Date of Birth</label>
                                        <input class="form-control" type="date" name="dob2" value="{{ $formattedDob }}" required/>
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label class="form-label">Passport No.</label>
                                        <input class="form-control" type="text" name="passport_no2"/>
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="text" class="form-control" name="email2" value="{{ $quote->email }}" required/>
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label class="form-label">Phone Number</label>
                                        <input type="text" class="form-control" name="phone_no2" value="{{ $quote->phone_no }}" required/>
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label class="form-label">Occupation</label>
                                        <input class="form-control" type="text" name="ocupation2" required/>
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label class="form-label">Nationality</label>
                                        <input class="form-control" type="text" name="nationalty2" required/>
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label class="form-label">Pre-Existing Medical Conditions</label>
                                        <input class="form-control" name="MedicalCondition2" type="text" maxlength="100" placeholder="None if N/A"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- General Info --}}
                    <div class="col-sm-12">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0" style="color:rgba(13,156,83,1);">General Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-sm-3 mb-3">
                                        <label class="form-label">Surname (Family)</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa fa-user"></i></span>
                                            <input type="text" class="form-control" name="surname" placeholder="Family surname" required/>
                                        </div>
                                    </div>
                                    <div class="col-sm-3 mb-3">
                                        <label class="form-label">State</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa fa-flag"></i></span>
                                            <select class="form-select" name="state" required>
                                                @include('air.insurance._state_options')
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-sm-3 mb-3">
                                        <label class="form-label">Address</label>
                                        <input class="form-control" type="text" name="address" required/>
                                    </div>
                                    <div class="col-sm-3 mb-3">
                                        <label class="form-label">Zip Code</label>
                                        <input class="form-control" type="text" name="zipcode" required/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Children section (JS duplicated) --}}
                    <div class="d-none">
                        <div class="col-sm-12 child">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0" style="color:rgba(13,156,83,1);">Child 1</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-sm-3 mb-3">
                                            <label class="form-label">Middle Name</label>
                                            <input type="text" class="form-control" name="middlenameC"/>
                                        </div>
                                        <div class="col-sm-3 mb-3">
                                            <label class="form-label">First Name</label>
                                            <input type="text" class="form-control" name="firstnameC"/>
                                        </div>
                                        <div class="col-sm-3 mb-3">
                                            <label class="form-label">Gender</label>
                                            <select class="form-select" name="genderC">
                                                <option value="">-- Gender --</option>
                                                <option value="1">Male</option>
                                                <option value="2">Female</option>
                                            </select>
                                        </div>
                                        <div class="col-sm-3 mb-3">
                                            <label class="form-label">Title</label>
                                            <select class="form-select" name="titleC">
                                                @include('air.insurance._title_options')
                                            </select>
                                        </div>
                                        <div class="col-sm-3 mb-3">
                                            <label class="form-label">Date of Birth</label>
                                            <input class="form-control" type="date" name="dobC"/>
                                        </div>
                                        <div class="col-sm-3 mb-3">
                                            <label class="form-label">Passport No.</label>
                                            <input class="form-control" type="text" name="passport_noC"/>
                                        </div>
                                        <div class="col-sm-3 mb-3">
                                            <label class="form-label">Nationality</label>
                                            <input class="form-control" type="text" name="nationaltyC"/>
                                        </div>
                                        <div class="col-sm-3 mb-3">
                                            <label class="form-label">Medical Conditions</label>
                                            <input class="form-control" name="MedicalConditionC" type="text" maxlength="100" placeholder="None if N/A"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="duplicate-container"></div>

                    <div class="col-sm-12 text-end mt-3">
                        <button type="submit" class="btn btn-pry">Next</button>
                    </div>
                </div>
            </form>
        </div>
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
            card.querySelector('h6').textContent = 'Child ' + i;
            card.querySelectorAll('input').forEach(el => el.setAttribute('name', el.getAttribute('name') + i));
            card.querySelectorAll('select').forEach(el => el.setAttribute('name', el.getAttribute('name') + i));
            container.appendChild(card);
        }
    }
    window.addEventListener('load', duplicateFormElements);
</script>

@endcomponent
