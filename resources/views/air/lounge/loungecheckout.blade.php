@component('layouts.app', ['title' => 'Lounge Checkout - TravelWheel'])

<style>
    .input-control {
        display:block; width:100%; padding:.175rem .25rem; font-size:1rem; font-weight:400;
        line-height:1.5; color:#212529; background-color:#fff;
        border-top-style:hidden; border-right-style:hidden; border-left-style:hidden;
        border-bottom:1px solid #ced4da; appearance:none;
        transition:border-color .15s ease-in-out,box-shadow .15s ease-in-out;
    }
    .no-outline:focus { outline:none; }
</style>

<section class="shadow-sm">
    <div class="container">
        <div class="row p-2 pt-5">
            <div class="col-sm-8 p-3">
                <h3><img src="{{ asset('assets/img/pp.png') }}" class="me-2" style="width:28px;" alt="lounge"> Airport Lounge Service</h3>
            </div>
        </div>

        <div class="row airport-form shadow p-4 mb-5">
            <div class="col-md-7">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" style="color:rgba(13,156,83,1);">Lounge Service Checkout</h5>
                        <small class="text-muted">Confirm your details</small>
                    </div>
                    <div class="card-body">

                        @php
                            $c_amount  = (int) str_replace(',', '', $dataform['amount'] ?? 0);
                            $vat       = round(($c_amount * 7.5) / 100, 2);
                            $total     = $c_amount + $vat;
                            $adultAmt  = (int) str_replace(',', '', $dataform['adultAmount'] ?? 0);
                            $childAmt  = (int) str_replace(',', '', $dataform['childAmount'] ?? 0);
                            $infantAmt = (int) str_replace(',', '', $dataform['infantAmount'] ?? 0);
                        @endphp

                        <form action="{{ route('air.lounge.purchase') }}" method="POST">
                            @csrf
                            {{-- Pass-through hidden fields --}}
                            <input type="hidden" name="lounge"     value="{{ $dataform['lounge'] ?? '' }}">
                            <input type="hidden" name="state"      value="{{ $dataform['state'] ?? '' }}">
                            <input type="hidden" name="airport"    value="{{ $dataform['airport'] ?? '' }}">
                            <input type="hidden" name="firstname"  value="{{ $dataform['firstname'] ?? '' }}">
                            <input type="hidden" name="lastname"   value="{{ $dataform['lastname'] ?? '' }}">
                            <input type="hidden" name="phone"      value="{{ $dataform['phone_no'] ?? '' }}">
                            <input type="hidden" name="email"      value="{{ $dataform['email'] ?? '' }}">
                            <input type="hidden" name="travel_date" value="{{ $dataform['service_date'] ?? '' }}">
                            <input type="hidden" name="d_time"     value="{{ $dataform['d_time'] ?? '' }}">
                            <input type="hidden" name="airline"    value="{{ $dataform['airline'] ?? '' }}">
                            <input type="hidden" name="nop"        value="{{ $dataform['nop'] ?? 0 }}">
                            <input type="hidden" name="noa"        value="{{ $dataform['adultValue'] ?? 0 }}">
                            <input type="hidden" name="noc"        value="{{ $dataform['childValue'] ?? 0 }}">
                            <input type="hidden" name="noi"        value="{{ $dataform['infantValue'] ?? 0 }}">
                            <input type="hidden" name="adultAmount"  value="{{ $adultAmt }}">
                            <input type="hidden" name="childAmount"  value="{{ $childAmt }}">
                            <input type="hidden" name="infantAmount" value="{{ $infantAmt }}">
                            <input type="hidden" name="c_amount"   value="{{ $c_amount }}">
                            <input type="hidden" name="vat"        value="{{ $vat }}">
                            <input type="hidden" name="p_amount"   value="{{ $total }}">

                            <div class="row">
                                <div class="col-sm-6 mb-1">
                                    <label class="form-label">First Name</label>
                                    <input type="text" disabled class="input-control no-outline" value="{{ $dataform['firstname'] ?? '' }}">
                                </div>
                                <div class="col-sm-6 mb-1">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" disabled class="input-control no-outline" value="{{ $dataform['lastname'] ?? '' }}">
                                </div>
                                <div class="col-sm-6 mb-1">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" disabled class="input-control no-outline" value="{{ $dataform['phone_no'] ?? '' }}">
                                </div>
                                <div class="col-sm-6 mb-1">
                                    <label class="form-label">Email</label>
                                    <input type="text" disabled class="input-control no-outline" value="{{ $dataform['email'] ?? '' }}">
                                </div>
                                <div class="col-sm-6 mb-1">
                                    <label class="form-label">State</label>
                                    <input type="text" disabled class="input-control no-outline" value="{{ $dataform['state'] ?? '' }}">
                                </div>
                                <div class="col-sm-6 mb-1">
                                    <label class="form-label">Airport</label>
                                    <input type="text" disabled class="input-control no-outline" value="{{ $dataform['airport'] ?? '' }}">
                                </div>
                                <div class="col-sm-6 mb-1">
                                    <label class="form-label">Travel Date</label>
                                    <input type="text" disabled class="input-control no-outline" value="{{ $dataform['service_date'] ?? '' }}">
                                </div>
                                <div class="col-sm-6 mb-1">
                                    <label class="form-label">Travel Time</label>
                                    <input type="text" disabled class="input-control no-outline" value="{{ $dataform['d_time'] ?? '' }}">
                                </div>
                                <div class="col-sm-6 mb-1">
                                    <label class="form-label">Airline</label>
                                    <input type="text" disabled class="input-control no-outline" value="{{ $dataform['airline'] ?? '' }}">
                                </div>
                                <div class="col-sm-6 mb-1">
                                    <label class="form-label">No. of Passengers</label>
                                    <input type="text" disabled class="input-control no-outline"
                                        value="Adult ({{ $dataform['adultValue'] ?? 0 }}) Child ({{ $dataform['childValue'] ?? 0 }}) Infant ({{ $dataform['infantValue'] ?? 0 }})">
                                </div>
                                <div class="col-sm-6 mb-1">
                                    <label class="form-label">Lounge</label>
                                    <input type="text" disabled class="input-control no-outline" value="{{ $dataform['lounge'] ?? '' }}">
                                </div>
                            </div>

                            <div class="col-12 p-3 text-center">
                                <a href="javascript:history.back()" class="btn btn-sm btn-secondary">Back to Edit</a>
                            </div>

                            {{-- Price summary (right column style inline) --}}
                            <div class="bg-light p-3 mt-3">
                                <h5>Lounge Service Price</h5>
                                <div class="row">
                                    <div class="col-8"><span>Amount For Adult ({{ $dataform['adultValue'] ?? 0 }})</span></div>
                                    <div class="col-4 text-end">₦{{ number_format($adultAmt) }}</div>
                                </div><hr>
                                <div class="row">
                                    <div class="col-8"><span>Amount For Child ({{ $dataform['childValue'] ?? 0 }})</span></div>
                                    <div class="col-4 text-end">₦{{ number_format($childAmt) }}</div>
                                </div><hr>
                                <div class="row">
                                    <div class="col-8"><span>Amount For Infant ({{ $dataform['infantValue'] ?? 0 }})</span></div>
                                    <div class="col-4 text-end">₦{{ number_format($infantAmt) }}</div>
                                </div><hr>
                                <div class="row">
                                    <div class="col-8"><span>Value Added Tax (VAT 7.5%)</span></div>
                                    <div class="col-4 text-end">₦{{ number_format($vat) }}</div>
                                </div><hr>
                                <div class="row">
                                    <div class="col-8"><b>Total Amount Including Tax</b></div>
                                    <div class="col-4 text-end"><b>₦{{ number_format($total) }}</b></div>
                                </div><hr>

                                <div class="row mt-3">
                                    <div class="col-sm-12 text-end">
                                        <button type="submit" class="btn btn-pry">Proceed to Payment</button>
                                    </div>
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
