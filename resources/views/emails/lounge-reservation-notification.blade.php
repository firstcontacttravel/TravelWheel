<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Lounge Booking</title>
    <style>
        body { margin: 0; padding: 0; background: #f4f6f9; font-family: 'Segoe UI', Arial, sans-serif; color: #1a1a1a; }
        .wrapper { max-width: 580px; margin: 32px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.07); }
        .header { background: #0d1883; padding: 28px 32px; }
        .header img { height: 32px; margin-bottom: 12px; }
        .header h1 { color: #ffffff; font-size: 20px; margin: 0 0 4px; font-weight: 700; }
        .header p { color: rgba(255,255,255,0.75); font-size: 13px; margin: 0; }
        .alert-bar { padding: 12px 20px; font-size: 13.5px; font-weight: 600; }
        .alert-ok { background: #e8f5e9; border-left: 4px solid #2e7d32; color: #1b5e20; }
        .alert-action { background: #fff4e5; border-left: 4px solid #e65100; color: #8a3a00; }
        .alert-action a { color: #8a3a00; }
        .body { padding: 28px 32px; }
        .section-title { font-size: 11.5px; font-weight: 700; color: #0d1883; text-transform: uppercase; letter-spacing: 0.09em; margin: 0 0 12px; }
        .details-card { background: #f7f8ff; border: 1px solid #e0e4f8; border-radius: 10px; padding: 16px 20px; margin-bottom: 20px; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eaecf8; font-size: 13px; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #888; font-weight: 500; width: 45%; }
        .detail-value { color: #1a1a1a; font-weight: 600; text-align: right; width: 55%; }
        .amount-row .detail-value { color: #0d1883; font-size: 14.5px; }
        .ref-row .detail-value { font-family: monospace; font-size: 12px; }
        .footer { background: #f7f8ff; padding: 18px 32px; text-align: center; border-top: 1px solid #e8eaf5; }
        .footer p { font-size: 12px; color: #aaa; margin: 0; }
    </style>
</head>
<body>
<div class="wrapper">

    <div class="header">
        <img src="{{ asset('assets/twlogo.png') }}" alt="TravelWheel Logo">
        <h1>🛋️ New Lounge Booking</h1>
        <p>A customer has paid for airport lounge access</p>
    </div>

    @if ($isLoungePair)
        <div class="alert-bar alert-action">
            ⚠️ This is a LoungePair lounge. Payment has been received; please place the booking on LoungePair's site
            @if ($booking->provider_url) (<a href="{{ $booking->provider_url }}">open the lounge on LoungePair</a>) @endif
            and send the customer their pass.
        </div>
    @else
        <div class="alert-bar alert-ok">
            ✅ Payment received. The customer can download their lounge pass from the website.
        </div>
    @endif

    <div class="body">
        <p class="section-title">Customer</p>
        <div class="details-card">
            <div class="detail-row"><span class="detail-label">Name</span><span class="detail-value">{{ $booking->fullname ?: '—' }}</span></div>
            <div class="detail-row"><span class="detail-label">Email</span><span class="detail-value">{{ $booking->email ?: '—' }}</span></div>
            <div class="detail-row"><span class="detail-label">Phone</span><span class="detail-value">{{ $booking->phone_no ?: '—' }}</span></div>
        </div>

        <p class="section-title">Lounge</p>
        <div class="details-card">
            <div class="detail-row"><span class="detail-label">Lounge</span><span class="detail-value">{{ $booking->lounge_name ?: '—' }}</span></div>
            <div class="detail-row"><span class="detail-label">Provider</span><span class="detail-value">{{ $isLoungePair ? 'LoungePair' : 'TravelWheel lounge' }}</span></div>
            @if ($where)
                <div class="detail-row"><span class="detail-label">Airport</span><span class="detail-value">{{ $where['airport'] ?? '—' }}@if ($where['iata']) ({{ $where['iata'] }})@endif</span></div>
                <div class="detail-row"><span class="detail-label">City / State</span><span class="detail-value">{{ $where['city'] ?? $state ?? '—' }}</span></div>
                <div class="detail-row"><span class="detail-label">Country</span><span class="detail-value">{{ $where['country'] ?? '—' }}</span></div>
                <div class="detail-row"><span class="detail-label">Terminal</span><span class="detail-value">{{ $where['terminal'] ?: '—' }}</span></div>
                @if ($where['access'])
                    <div class="detail-row"><span class="detail-label">Lounge access</span><span class="detail-value">{{ $where['access'] }}</span></div>
                @endif
            @elseif ($state)
                <div class="detail-row"><span class="detail-label">State</span><span class="detail-value">{{ $state }}</span></div>
            @endif
        </div>

        <p class="section-title">Travel</p>
        <div class="details-card">
            <div class="detail-row"><span class="detail-label">Travel date</span><span class="detail-value">{{ $booking->travel_date?->format('D, j M Y') ?? '—' }}</span></div>
            <div class="detail-row"><span class="detail-label">Departure time</span><span class="detail-value">{{ $booking->d_time ?: '—' }}</span></div>
            <div class="detail-row"><span class="detail-label">Flight ticket no.</span><span class="detail-value">{{ $booking->ticket_no ?: '—' }}</span></div>
            <div class="detail-row"><span class="detail-label">Guests</span><span class="detail-value">{{ (int) $booking->noa }} adult(s) · {{ (int) $booking->noc }} child(ren) · {{ (int) $booking->noi }} infant(s)</span></div>
            <div class="detail-row"><span class="detail-label">Total guests</span><span class="detail-value">{{ (int) $booking->nop }}</span></div>
        </div>

        <p class="section-title">Payment</p>
        <div class="details-card">
            <div class="detail-row"><span class="detail-label">Adults</span><span class="detail-value">₦{{ number_format((float) $booking->amountA, 2) }}</span></div>
            <div class="detail-row"><span class="detail-label">Children</span><span class="detail-value">₦{{ number_format((float) $booking->amountC, 2) }}</span></div>
            @if ($infantAmount > 0)
                <div class="detail-row"><span class="detail-label">Infants</span><span class="detail-value">₦{{ number_format($infantAmount, 2) }}</span></div>
            @endif
            <div class="detail-row"><span class="detail-label">Subtotal</span><span class="detail-value">₦{{ number_format((float) $booking->amount, 2) }}</span></div>
            <div class="detail-row"><span class="detail-label">VAT</span><span class="detail-value">₦{{ number_format((float) $booking->vat, 2) }}</span></div>
            <div class="detail-row amount-row"><span class="detail-label">Total paid</span><span class="detail-value">₦{{ number_format($totalPaid, 2) }}</span></div>
            <div class="detail-row"><span class="detail-label">Payment method</span><span class="detail-value">{{ ucfirst($booking->payment_option ?: 'seerbit') }}</span></div>
            <div class="detail-row ref-row"><span class="detail-label">Payment reference</span><span class="detail-value">{{ $booking->trans_id }}</span></div>
            <div class="detail-row"><span class="detail-label">Booked</span><span class="detail-value">{{ $booking->created_at?->format('j M Y, g:i A') }}</span></div>
        </div>
    </div>

    <div class="footer">
        <p>Reply to this email to contact the customer directly.</p>
    </div>
</div>
</body>
</html>
