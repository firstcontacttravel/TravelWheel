@component('layouts.app', ['title' => 'Lounge Checkout - TravelWheel'])
@include('air.lounge.partials.lounge-ui')

@php
    $c_amount  = (int) str_replace(',', '', $dataform['amount'] ?? 0);
    $vat       = round(($c_amount * 7.5) / 100, 2);
    $total     = $c_amount + $vat;
    $adultAmt  = (int) str_replace(',', '', $dataform['adultAmount'] ?? 0);
    $childAmt  = (int) str_replace(',', '', $dataform['childAmount'] ?? 0);
    $infantAmt = (int) str_replace(',', '', $dataform['infantAmount'] ?? 0);
@endphp

<section class="lounge-page">
    <div class="lounge-wrap">
        <div class="lounge-steps">
            <span class="lounge-step"><x-ph-icon name="map-pin" /> {{ $dataform['lounge'] ?? '' }}</span>
            <span class="lounge-step lounge-step-active"><x-ph-icon name="receipt" /> Checkout</span>
            <span class="lounge-step"><x-ph-icon name="credit-card" /> Pay</span>
        </div>

        <form action="{{ route('air.lounge.purchase') }}" method="POST">
            @csrf
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

            <div class="lounge-checkout-grid">
                <div class="lounge-panel">
                    <div class="lounge-section-title"><x-ph-icon name="list-magnifying-glass" /> Confirm Details</div>
                    <div class="lounge-detail-grid">
                        <div class="lounge-detail"><span>Lounge</span><strong>{{ $dataform['lounge'] ?? '' }}</strong></div>
                        <div class="lounge-detail"><span>First Name</span><strong>{{ $dataform['firstname'] ?? '' }}</strong></div>
                        <div class="lounge-detail"><span>Last Name</span><strong>{{ $dataform['lastname'] ?? '' }}</strong></div>
                        <div class="lounge-detail"><span>Phone Number</span><strong>{{ $dataform['phone_no'] ?? '' }}</strong></div>
                        <div class="lounge-detail"><span>Email</span><strong>{{ $dataform['email'] ?? '' }}</strong></div>
                        <div class="lounge-detail"><span>State</span><strong>{{ $dataform['state'] ?? '' }}</strong></div>
                        <div class="lounge-detail"><span>Airport</span><strong>{{ $dataform['airport'] ?? '' }}</strong></div>
                        <div class="lounge-detail"><span>Service Date</span><strong>{{ $dataform['service_date'] ?? '' }}</strong></div>
                        <div class="lounge-detail"><span>Time</span><strong>{{ $dataform['d_time'] ?? '' }}</strong></div>
                        <div class="lounge-detail"><span>Airline</span><strong>{{ $dataform['airline'] ?? '' }}</strong></div>
                        <div class="lounge-detail"><span>Passengers</span><strong>Adult ({{ $dataform['adultValue'] ?? 0 }}) Child ({{ $dataform['childValue'] ?? 0 }}) Infant ({{ $dataform['infantValue'] ?? 0 }})</strong></div>
                    </div>

                    <a href="javascript:history.back()" class="lounge-btn lounge-btn-secondary mt-4">
                        <x-ph-icon name="arrow-left" /> Back to Edit
                    </a>
                </div>

                <aside class="lounge-summary lounge-panel">
                    <div class="lounge-section-title"><x-ph-icon name="wallet" /> Lounge Service Price</div>
                    <div class="lounge-total-row">
                        <span>Amount For Adult ({{ $dataform['adultValue'] ?? 0 }})</span>
                        <strong>₦{{ number_format($adultAmt) }}</strong>
                    </div>
                    <div class="lounge-total-row">
                        <span>Amount For Child ({{ $dataform['childValue'] ?? 0 }})</span>
                        <strong>₦{{ number_format($childAmt) }}</strong>
                    </div>
                    <div class="lounge-total-row">
                        <span>Amount For Infant ({{ $dataform['infantValue'] ?? 0 }})</span>
                        <strong>₦{{ number_format($infantAmt) }}</strong>
                    </div>
                    <div class="lounge-total-row">
                        <span>VAT (7.5%)</span>
                        <strong>₦{{ number_format($vat) }}</strong>
                    </div>
                    <div class="lounge-total-row lounge-grand-total">
                        <span>Total</span>
                        <strong>₦{{ number_format($total) }}</strong>
                    </div>

                    <button type="submit" class="lounge-btn w-100 mt-3">
                        Proceed to Payment <x-ph-icon name="arrow-right" />
                    </button>
                </aside>
            </div>
        </form>
    </div>
</section>

@endcomponent
