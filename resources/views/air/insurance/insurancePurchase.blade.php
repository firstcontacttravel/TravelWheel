@component('layouts.app', ['title' => 'Insurance Purchase - TravelWheel'])

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
                        <span class="text-muted">Fill in your personal details to proceed with the purchase.</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row airport-form shadow p-4 mb-5">
            <div class="col-sm-5 mb-3">
                <div class="bg-light p-3" style="border-radius:10px;">
                    <h5>PRICING</h5>
                    <p><b>Quote Price:</b> <span style="font-size:20px; font-weight:bolder;">₦{{ number_format($quote->amount, 2) }}</span></p>
                </div>
            </div>

            <div class="col-sm-7">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" style="color:rgba(13,156,83,1);">Insurance Purchase</h5>
                        <small class="text-muted">Customer Details</small>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('air.insurance.purchase') }}" method="POST">
                            @csrf
                            <input type="hidden" name="bookingTypeId" value="{{ $quote->bookingTypeId }}"/>
                            <input type="hidden" name="qouteId" value="{{ $quote->quoteRequestId }}"/>
                            <input type="hidden" name="amount" value="{{ $quote->amount }}"/>

                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label class="form-label">Surname</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa fa-user"></i></span>
                                            <input type="text" class="form-control" name="surname" placeholder="Surname" required/>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label class="form-label">Middle Name</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa fa-user"></i></span>
                                            <input type="text" class="form-control" name="middlename" placeholder="Middle name"/>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label class="form-label">First Name</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa fa-user"></i></span>
                                            <input type="text" class="form-control" name="firstname" placeholder="First name" required/>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label class="form-label">Gender</label>
                                        <select class="form-select" name="gender" required>
                                            <option value="">-- Select Gender --</option>
                                            <option value="1">Male</option>
                                            <option value="2">Female</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label class="form-label">Title</label>
                                        <select class="form-select" name="title" required>
                                            @include('air.insurance._title_options')
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label class="form-label">Date of Birth</label>
                                        @php
                                            try {
                                                $formattedDob = \Carbon\Carbon::parse($quote->dob)->format('Y-m-d');
                                            } catch (\Exception $e) {
                                                $formattedDob = '';
                                            }
                                        @endphp
                                        <input class="form-control" type="date" name="dob" value="{{ $formattedDob }}" required/>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email Address</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                                            <input type="text" class="form-control" name="email" value="{{ $quote->email }}" placeholder="Email" required/>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label class="form-label">Phone Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa fa-phone"></i></span>
                                            <input type="text" class="form-control" name="phone_no" value="{{ $quote->phone_no }}" placeholder="Phone number" required/>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label class="form-label">State of Residence</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa fa-flag"></i></span>
                                            <select class="form-select" name="state" required>
                                                @include('air.insurance._state_options')
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label class="form-label">Address</label>
                                        <input class="form-control" type="text" name="address" required/>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label class="form-label">Zip Code</label>
                                        <input class="form-control" type="text" name="zipcode" required/>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label class="form-label">Passport No.</label>
                                        <input class="form-control" type="text" name="passport_no" required/>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label class="form-label">Occupation</label>
                                        <input class="form-control" type="text" name="ocupation" required/>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nationality</label>
                                        <input class="form-control" type="text" name="nationalty" required/>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-3">
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
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-3 pt-2">
                                        <input class="form-check-input" type="checkbox" id="medical" name="medical_check"/>
                                        <label class="form-check-label" for="medical">Has Pre-Existing Medical Condition?</label>
                                        <div class="hidden" id="medicalhid">
                                            <label class="form-label mt-2">Details of Medical Conditions</label>
                                            <input class="form-control" name="MedicalCondition" type="text" maxlength="100" placeholder="Describe condition"/>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <button type="submit" class="btn btn-pry">Next</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.getElementById('medical').addEventListener('change', function () {
        document.getElementById('medicalhid').style.display = this.checked ? 'block' : 'none';
    });
</script>

@endcomponent
