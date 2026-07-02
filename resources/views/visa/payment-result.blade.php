@component('layouts.app', ['title' => $payment->status === 'paid' ? 'Visa Payment Confirmed' : 'Visa Payment Status'])
<link rel="stylesheet" href="{{ asset('css/visa-portal.css') }}?v={{ filemtime(public_path('css/visa-portal.css')) }}">
<div class="vps-page">
    @if($payment->status === 'paid')
        <section class="vps-hero" aria-labelledby="payment-result-title">
            <div class="vps-glow vps-glow--one"></div><div class="vps-glow vps-glow--two"></div>
            <div class="vps-hero__inner">
                <div class="vps-success-mark"><x-ui.icon name="check-circle" :size="48" /></div>
                <p class="vps-kicker">Payment successful</p>
                <h1 id="payment-result-title">Your visa application is on its way</h1>
                <p class="vps-lead">Your payment has been confirmed and your application has been submitted securely to TravelWheel.</p>
                <div class="vps-actions">
                    <a class="vps-button vps-button--primary" href="{{ route('visa.portal.entry') }}">Track your application <x-ui.icon name="arrow-right" :size="18" /></a>
                    <a class="vps-button vps-button--quiet" href="{{ route('air.visa') }}">Return to visa search</a>
                </div>
            </div>
        </section>
        <section class="vps-content" aria-label="Payment confirmation details">
            <div class="vps-details">
                <div><span>Application reference</span><strong>{{ $payment->application->reference }}</strong></div>
                <div><span>Payment reference</span><strong>{{ $payment->reference }}</strong></div>
                <div><span>Amount confirmed</span><strong>{{ $payment->expected_currency }} {{ number_format($payment->expected_amount, 2) }}</strong></div>
                <div><span>Visa product</span><strong>{{ $payment->application->product->name }}</strong></div>
            </div>
            <div class="vps-next">
                <div class="vps-next__intro"><p class="vps-kicker">What happens next</p><h2>We’ll keep you informed</h2><p>Use your application reference and email address to open the secure customer portal at any time.</p></div>
                <ol>
                    <li><b>1</b><span><strong>Payment recorded</strong><small>Your confirmed payment is attached to this application.</small></span></li>
                    <li><b>2</b><span><strong>Application review</strong><small>Our visa team checks the submitted details and documents.</small></span></li>
                    <li><b>3</b><span><strong>Status updates</strong><small>You’ll receive an email whenever action or a decision is available.</small></span></li>
                </ol>
            </div>
            <p class="vps-note"><x-ui.icon name="shield" :size="18" /> Keep your application reference safe. TravelWheel will never ask you to share your portal access code.</p>
        </section>
    @else
        <section class="vps-attention" aria-labelledby="payment-result-title">
            <div class="vps-attention__icon">!</div><p class="vps-kicker">Payment status</p><h1 id="payment-result-title">Payment needs attention</h1>
            <p>We could not verify this payment against your quotation. If you were debited, do not pay again—contact TravelWheel support with the references below.</p>
            <div class="vps-details vps-details--compact"><div><span>Application</span><strong>{{ $payment->application->reference }}</strong></div><div><span>Payment</span><strong>{{ $payment->reference }}</strong></div><div><span>Expected amount</span><strong>{{ $payment->expected_currency }} {{ number_format($payment->expected_amount, 2) }}</strong></div></div>
            <a class="vps-button vps-button--primary" href="{{ route('visa.portal.entry') }}">Open customer portal</a>
        </section>
    @endif
</div>
@endcomponent
