@component('layouts.app', ['title' => 'Insurance Checkout - TravelWheel'])
@include('air.insurance.partials.insurance-ui')

@php
    $amount = (float) ($dataform['amount'] ?? 0);
    $vat    = round($amount * 0.075, 2);
    $total  = round($amount + $vat, 2);
@endphp

<section class="insurance-page">
    <div class="insurance-wrap">
        <div class="insurance-steps">
            <span class="insurance-step"><x-ph-icon name="clipboard-text" /> Get quote</span>
            <span class="insurance-step"><x-ph-icon name="tag" /> Your quote</span>
            <span class="insurance-step"><x-ph-icon name="identification-card" /> Add details</span>
            <span class="insurance-step insurance-step-active"><x-ph-icon name="credit-card" /> Checkout</span>
        </div>

        <div class="insurance-hero-main mb-4">
            <div class="insurance-kicker"><x-ph-icon name="receipt" /> Next of Kin &amp; Payment</div>
            <h1 class="insurance-title">Confirm your insurance booking</h1>
            <p class="insurance-copy">Add your next of kin details and review your payment summary before proceeding.</p>
        </div>

        <form action="{{ route('air.insurance.pay') }}" method="POST">
            @csrf
            <input type="hidden" name="c_amount" value="{{ $amount }}">
            <input type="hidden" name="vat" value="{{ $vat }}">
            <input type="hidden" name="p_amount" value="{{ $total }}">

            <div class="insurance-checkout-grid">
                <div class="insurance-panel">
                    <div class="insurance-section-title"><x-ph-icon name="phone-call" /> Next of Kin</div>
                    <div class="row">
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="nok_fullname" placeholder="Full name" required>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="nok_address" placeholder="Address" required>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Phone Number</label>
                            <input type="text" class="form-control" name="nok_phone" placeholder="Phone number" required>
                        </div>
                        <div class="col-sm-6 insurance-field">
                            <label class="form-label">Relationship</label>
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

                    <a href="javascript:history.back()" class="insurance-btn insurance-btn-secondary">
                        <x-ph-icon name="arrow-left" /> Back to Edit
                    </a>
                </div>

                <aside class="insurance-summary insurance-panel">
                    <div class="insurance-section-title"><x-ph-icon name="wallet" /> Payment Summary</div>
                    <div class="insurance-total-row">
                        <span>Quote Price</span>
                        <strong>&#8358;{{ number_format($amount, 2) }}</strong>
                    </div>
                    <div class="insurance-total-row">
                        <span>VAT (7.5%)</span>
                        <strong>&#8358;{{ number_format($vat, 2) }}</strong>
                    </div>
                    <div class="insurance-total-row insurance-grand-total">
                        <span>Total</span>
                        <strong>&#8358;{{ number_format($total, 2) }}</strong>
                    </div>

                    <button type="submit" class="insurance-btn w-100 mt-3">
                        <x-ph-icon name="lock-simple" /> Pay &#8358;{{ number_format($total, 2) }} with Seerbit
                    </button>
                </aside>
            </div>
        </form>
    </div>
</section>

@endcomponent
