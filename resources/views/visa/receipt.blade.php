@component('layouts.app', ['title' => 'Visa Payment Receipt'])
<link rel="stylesheet" href="{{ asset('css/visa-portal.css') }}?v={{ filemtime(public_path('css/visa-portal.css')) }}">
<div class="vp-page"><section class="vp-receipt"><header><div><p class="vp-eyebrow">Payment receipt</p><h1>TravelWheel Visa</h1></div><span class="vp-status vp-status--paid">Paid</span></header>
    <div class="vp-receipt-meta"><div><span>Application</span><strong>{{ $application->reference }}</strong></div><div><span>Payment</span><strong>{{ $payment->reference }}</strong></div><div><span>Date</span><strong>{{ $payment->verified_at?->format('d M Y, H:i') }}</strong></div><div><span>Gateway</span><strong>{{ ucfirst($payment->provider) }}</strong></div></div>
    <h2>{{ $application->product->name }}</h2>@foreach($payment->quote->items->where('pay_online', true) as $item)<div class="vp-receipt-line"><span>{{ $item->name }}</span><strong>{{ $item->checkout_currency }} {{ number_format($item->checkout_total, 2) }}</strong></div>@endforeach
    <div class="vp-receipt-total"><span>Total paid toward quotation</span><strong>{{ $payment->expected_currency }} {{ number_format($payment->expected_amount, 2) }}</strong></div>
    @if((float)$payment->verified_amount > (float)$payment->expected_amount)<p class="vp-fee-note">The card charge included a {{ $payment->expected_currency }} {{ number_format($payment->verified_amount-$payment->expected_amount,2) }} payment-gateway fee.</p>@endif
    <footer><a class="vp-outline" href="{{ route('visa.portal.show', $application) }}">Back to application</a><button class="vp-button" onclick="window.print()">Print receipt</button></footer>
</section></div>
@endcomponent
