@component('layouts.app', ['title' => 'TravelFlex Approved'])
@php
    $plan = $application->repayment_plan ?? [];
    $money = fn ($value) => 'NGN ' . number_format((float) $value, 2);
    $flight = $booking->flight_snapshot ?? [];
    $segments = $flight['segments'] ?? [];
    $first = $segments[0] ?? [];
    $last = $segments ? $segments[array_key_last($segments)] : [];
    $bankTransferAvailable = config('travelwheel.travelflex_bank_accounts', []) !== [];
@endphp
<style>
    body{background:#f6f7fb}main.navbarmain.upper-space:has(.tfa-payment-page){margin-top:113px!important;padding-top:0!important}.tfa-wrap{max-width:980px;margin:0 auto;padding:34px 18px 70px;font-family:var(--font-primary,Arial,sans-serif);color:#101828}.tfa-hero{background:#fff;border:1px solid #e4e7ec;border-top:4px solid #049a63;padding:28px;border-radius:8px}.tfa-kicker{font-size:12px;font-weight:800;color:#049a63;text-transform:uppercase}.tfa-title{font-size:30px;font-weight:800;margin-top:7px}.tfa-copy{color:#667085;line-height:1.65;margin-top:8px}.tfa-grid{display:grid;grid-template-columns:1.3fr .7fr;gap:18px;margin-top:18px}.tfa-card{background:#fff;border:1px solid #e4e7ec;border-radius:8px;padding:22px}.tfa-row{display:flex;justify-content:space-between;gap:20px;padding:11px 0;border-bottom:1px solid #eef1f5}.tfa-row:last-child{border:0}.tfa-row span{color:#667085}.tfa-row strong{text-align:right}.tfa-alert{margin-top:16px;padding:13px 15px;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;border-radius:7px}.tfa-options{display:grid;gap:10px;margin-top:14px}.tfa-option{display:flex;gap:12px;align-items:flex-start;border:1px solid #dfe3ea;border-radius:7px;padding:15px;cursor:pointer}.tfa-option:has(input:checked){border-color:#39328f;background:#f7f7ff}.tfa-option input{margin-top:3px}.tfa-option strong{display:block}.tfa-option small{display:block;color:#667085;margin-top:3px;line-height:1.5}.tfa-submit{width:100%;margin-top:14px;border:0;border-radius:7px;padding:14px;background:#39328f;color:#fff;font-weight:800;cursor:pointer}@media(max-width:700px){main.navbarmain.upper-space:has(.tfa-payment-page){margin-top:104px!important}.tfa-grid{grid-template-columns:1fr}.tfa-title{font-size:24px}.tfa-wrap{padding:18px 12px 56px}.tfa-hero,.tfa-card{padding:18px}.tfa-row{gap:12px}}
</style>
<main class="tfa-wrap tfa-payment-page">
    <section class="tfa-hero">
        <div class="tfa-kicker">Fast Credit decision</div>
        <div class="tfa-title">Your TravelFlex application is approved</div>
        <p class="tfa-copy">Review the held itinerary and complete the down payment before the airline deadline. Your ticket is issued only after the payment is verified.</p>
        <div class="tfa-alert">Complete payment by <strong>{{ $paymentDeadline?->timezone('Africa/Lagos')->format('D, d M Y H:i') }} WAT</strong>. We reserve two hours before the airline deadline for payment verification and ticketing.</div>
    </section>
    @if($errors->any())<div class="tfa-alert">{{ $errors->first() }}</div>@endif
    <div class="tfa-grid">
        <section class="tfa-card">
            <h2 style="font-size:18px;margin:0 0 8px;">Held itinerary</h2>
            <div class="tfa-row"><span>Route</span><strong>{{ $first['from'] ?? '-' }} to {{ $last['to'] ?? '-' }}</strong></div>
            <div class="tfa-row"><span>Airline</span><strong>{{ $flight['airline'] ?? $booking->airline ?? '-' }}</strong></div>
            <div class="tfa-row"><span>Booking reference</span><strong>{{ $booking->booking_ref }}</strong></div>
            <div class="tfa-row"><span>Ticket cost</span><strong>{{ $money($plan['ticket_cost'] ?? $booking->total_price) }}</strong></div>
            <div class="tfa-row"><span>Down payment</span><strong style="color:#049a63">{{ $money($plan['down_payment'] ?? $application->down_payment) }}</strong></div>
            <div class="tfa-row"><span>Financed amount</span><strong>{{ $money($plan['loan_amount'] ?? $plan['remaining_balance'] ?? 0) }}</strong></div>
            <div class="tfa-row"><span>Repayment plan</span><strong>{{ $plan['repayment_plan'] ?? '-' }}</strong></div>
        </section>
        <form class="tfa-card" method="POST" action="{{ route('flights.travelflex.approved.payment') }}">
            @csrf
            <h2 style="font-size:18px;margin:0;">Down payment method</h2>
            <div class="tfa-options">
                <label class="tfa-option"><input type="radio" name="pay_method" value="gateway" checked><span><strong>Pay online</strong><small>Card, transfer or USSD through SeerBit.</small></span></label>
                @if($bankTransferAvailable)
                    <label class="tfa-option"><input type="radio" name="pay_method" value="bank_transfer"><span><strong>Bank transfer</strong><small>Transfer manually and submit your reference for verification.</small></span></label>
                @endif
            </div>
            <button class="tfa-submit" type="submit">Continue to down payment</button>
        </form>
    </div>
</main>
@endcomponent
