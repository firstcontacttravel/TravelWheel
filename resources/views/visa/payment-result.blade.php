@component('layouts.app', ['title' => 'Visa Payment Status'])
<link rel="stylesheet" href="{{ asset('css/visa-application.css') }}">
<div class="va-page"><div class="va-card" style="max-width:680px;margin:48px auto;text-align:center">
    @if($payment->status === 'paid')
        <x-ui.icon name="check-circle" :size="56" style="color:#009933"/><h1>Payment confirmed</h1>
        <p>Your visa application has been submitted successfully.</p>
        <p><a class="va-primary" style="display:inline-flex;text-decoration:none;margin-top:12px" href="{{ route('visa.portal.entry') }}">Track your application</a></p>
    @else
        <h1>Payment needs attention</h1><p>We could not verify the payment against your quotation. Contact support if you were debited.</p>
    @endif
    <p><strong>Application:</strong> {{ $payment->application->reference }}</p><p><strong>Payment:</strong> {{ $payment->reference }}</p>
    <p><strong>Amount:</strong> {{ $payment->expected_currency }} {{ number_format($payment->expected_amount,2) }}</p>
</div></div>
@endcomponent
