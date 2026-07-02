@component('layouts.app', ['title' => 'Insurance Checkout - TravelWheel'])

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
                        <span class="text-muted">Next of Kin &amp; Checkout</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row airport-form shadow p-4 mb-5">
            @php
                $amount   = (float) ($dataform['amount'] ?? 0);
                $vat      = round($amount * 0.075, 2);
                $total    = round($amount + $vat, 2);
            @endphp

            <div class="col-sm-5 mb-3">
                <div class="bg-light p-3" style="border-radius:10px;">
                    <h5>PRICING</h5>
                    <table class="table table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td>Quote Price:</td>
                                <td class="text-end fw-bold">₦{{ number_format($amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td>VAT (7.5%):</td>
                                <td class="text-end">₦{{ number_format($vat, 2) }}</td>
                            </tr>
                            <tr class="border-top">
                                <td><b>Total:</b></td>
                                <td class="text-end" style="font-size:20px; font-weight:bolder;">₦{{ number_format($total, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-sm-7">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" style="color:rgba(13,156,83,1);">Next of Kin &amp; Payment</h5>
                        <small class="text-muted">Step 2 of 2</small>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('air.insurance.pay') }}" method="POST">
                            @csrf
                            <input type="hidden" name="c_amount" value="{{ $amount }}"/>
                            <input type="hidden" name="vat" value="{{ $vat }}"/>
                            <input type="hidden" name="p_amount" value="{{ $total }}"/>

                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label class="form-label">NOK Full Name</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa fa-user"></i></span>
                                            <input type="text" class="form-control" name="nok_fullname" placeholder="Full name" required/>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label class="form-label">NOK Address</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa fa-home"></i></span>
                                            <input type="text" class="form-control" name="nok_address" placeholder="Address" required/>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label class="form-label">NOK Phone Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa fa-phone"></i></span>
                                            <input type="text" class="form-control" name="nok_phone" placeholder="Phone number" required/>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label class="form-label">NOK Relationship</label>
                                        <select class="form-select" name="nok_relationship" required>
                                            <option value="">-- Relationship --</option>
                                            <option value="Spouse">Spouse</option>
                                            <option value="Parent">Parent</option>
                                            <option value="Sibling">Sibling</option>
                                            <option value="Child">Child</option>
                                            <option value="Friend">Friend</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-12 mt-2">
                                    <button type="submit" class="btn btn-pry btn-lg w-100">
                                        <i class="fa fa-lock me-1"></i> Pay ₦{{ number_format($total, 2) }} with Seerbit
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endcomponent
