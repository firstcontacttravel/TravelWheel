<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Complete your booking - TravelWheel</title>
    <style>
        body { margin: 0; padding: 32px 16px; background: #f4f6f9; color: #1a1a1a; font-family: 'Segoe UI', Arial, sans-serif; }
        .card { max-width: 460px; margin: 0 auto; background: #fff; border-radius: 16px; padding: 28px; box-shadow: 0 4px 20px rgba(0, 0, 0, .07); }
        h1 { margin: 0 0 6px; font-size: 21px; color: #0d1883; }
        p { margin: 0 0 18px; font-size: 14.5px; line-height: 1.6; color: #5c6070; }
        .row { display: flex; justify-content: space-between; gap: 16px; padding: 10px 0; border-top: 1px solid #eef0f6; font-size: 14px; }
        .row span:first-child { color: #5c6070; }
        .row span:last-child { text-align: right; font-weight: 600; }
        .total span:last-child { color: #0d1883; font-size: 17px; }
        button { width: 100%; margin-top: 18px; padding: 13px; border: 0; border-radius: 24px; background: #0d9c53; color: #fff; font-size: 15px; font-weight: 600; cursor: pointer; }
        .error { margin-bottom: 16px; padding: 10px 12px; border-radius: 8px; background: #fff0f0; color: #c0392b; font-size: 13.5px; }
    </style>
</head>
<body>
<div class="card">
    @if ($state === 'paid')
        <h1>This booking is paid</h1>
        <p>Thank you. Your confirmation has been sent to your email. Reference: <strong>{{ $booking->payment_reference }}</strong>.</p>
    @elseif ($state !== 'pending')
        <h1>This booking is no longer available</h1>
        <p>Please contact {{ config('travelwheel.reservations_email') }} and quote <strong>{{ $booking->payment_reference }}</strong>.</p>
    @else
        <h1>Complete your booking</h1>
        <p>Dear {{ $customer }}, your {{ $product }} booking is confirmed once payment is received.</p>

        @if (session('error'))
            <div class="error" role="alert">{{ session('error') }}</div>
        @endif

        <div class="row"><span>Reference</span><span>{{ $booking->payment_reference }}</span></div>
        @foreach ($summary as $label => $value)
            <div class="row"><span>{{ $label }}</span><span>{{ $value }}</span></div>
        @endforeach
        <div class="row total"><span>Amount</span><span>&#8358;{{ number_format((float) $total) }}</span></div>

        <form method="POST" action="{{ route('staff-bookings.pay.start', $token) }}">
            @csrf
            <button type="submit">Pay &#8358;{{ number_format((float) $total) }}</button>
        </form>
    @endif
</div>
</body>
</html>
