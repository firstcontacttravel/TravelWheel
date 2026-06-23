{{-- resources/views/emails/payment-receipt.blade.php --}}
@php
    $passengers = \App\Support\FlightDisplay::passengers($booking->passengers_snapshot ?? []);
    $firstPax = collect($passengers)->first();
    $firstName = $firstPax['first_name'] ?? 'Traveller';
    $cabinLabel = \App\Support\FlightDisplay::cabin($booking->flight_snapshot ?? [], $booking);
    $currency = $booking->payment_currency ?: ($booking->currency ?? 'NGN');
    $sym = match($currency) { 'NGN' => 'NGN ', 'USD' => '$', 'GBP' => 'GBP ', 'EUR' => 'EUR ', default => $currency . ' ' };
    $expectedAmount = (float) ($booking->payment_amount ?? $booking->total_price ?? 0);
    $chargedAmount = (float) ($booking->payment_charged_amount ?? $expectedAmount);
    $charges = max(0, $chargedAmount - $expectedAmount);
    $extraServices = $booking->extra_services_snapshot ?? [];
    $baggageItems = $extraServices['baggage'] ?? [];
    $mealItems = $extraServices['meal'] ?? [];
    $primary = '#303191';
    $accent = '#009933';
    $ink = '#111827';
    $muted = '#667085';
    $line = '#e6e8ee';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Receipt - {{ $booking->booking_ref }}</title>
</head>
<body style="margin:0;padding:0;background:#f8f9fc;font-family:Arial,Helvetica,sans-serif;color:{{ $ink }};">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#f8f9fc;padding:28px 0;">
<tr><td align="center" style="padding:0 12px;">
<table width="640" cellpadding="0" cellspacing="0" role="presentation" style="width:640px;max-width:100%;background:#ffffff;border:1px solid {{ $line }};border-radius:14px;overflow:hidden;">
    <tr>
        <td style="background:linear-gradient(105deg,#303191 0%,#254277 56%,#0c6b64 100%);padding:30px;color:#ffffff;">
            <div style="font-size:22px;font-weight:900;">TravelWheel</div>
            <div style="font-size:11px;font-weight:800;color:#dfffea;text-transform:uppercase;letter-spacing:.08em;margin-top:4px;">Payment receipt</div>
            <div style="font-size:25px;font-weight:900;line-height:1.25;margin-top:24px;">Payment received</div>
            <div style="font-size:13px;line-height:1.7;color:#edf4ff;margin-top:8px;max-width:520px;">
                Hi {{ $firstName }}, we received your payment for booking {{ $booking->booking_ref }}.
            </div>
        </td>
    </tr>
    <tr>
        <td style="padding:24px 30px 0;">
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#eafff0;border:1px solid #bdecc9;border-radius:12px;">
                <tr>
                    <td style="padding:18px;">
                        <div style="font-size:10px;font-weight:900;color:{{ $accent }};text-transform:uppercase;letter-spacing:.08em;">Total charged</div>
                        <div style="font-size:28px;font-weight:900;color:{{ $ink }};margin-top:5px;">{{ $sym }}{{ number_format($chargedAmount, 2) }}</div>
                    </td>
                    <td align="right" style="padding:18px;">
                        <div style="font-size:10px;font-weight:900;color:{{ $muted }};text-transform:uppercase;letter-spacing:.08em;">Payment reference</div>
                        <div style="font-size:14px;font-weight:900;color:{{ $primary }};font-family:'Courier New',monospace;margin-top:6px;">{{ $booking->payment_reference ?: '-' }}</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="padding:20px 30px 0;">
            <div style="font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;color:{{ $muted }};margin-bottom:10px;">Receipt details</div>
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;font-size:13px;">
                <tr><td style="padding:10px 0;border-bottom:1px solid #eef0f4;color:{{ $muted }};">Booking reference</td><td align="right" style="padding:10px 0;border-bottom:1px solid #eef0f4;font-weight:800;">{{ $booking->booking_ref }}</td></tr>
                <tr><td style="padding:10px 0;border-bottom:1px solid #eef0f4;color:{{ $muted }};">Gateway</td><td align="right" style="padding:10px 0;border-bottom:1px solid #eef0f4;font-weight:800;">{{ ucfirst($booking->payment_gateway ?? 'SeerBit') }}</td></tr>
                <tr><td style="padding:10px 0;border-bottom:1px solid #eef0f4;color:{{ $muted }};">Payment date</td><td align="right" style="padding:10px 0;border-bottom:1px solid #eef0f4;font-weight:800;">{{ $booking->payment_verified_at ? $booking->payment_verified_at->timezone('Africa/Lagos')->format('d M Y, H:i') : '-' }}</td></tr>
                <tr><td style="padding:10px 0;border-bottom:1px solid #eef0f4;color:{{ $muted }};">Route</td><td align="right" style="padding:10px 0;border-bottom:1px solid #eef0f4;font-weight:800;">{{ $booking->route ?: '-' }}</td></tr>
                <tr><td style="padding:10px 0;border-bottom:1px solid #eef0f4;color:{{ $muted }};">Cabin</td><td align="right" style="padding:10px 0;border-bottom:1px solid #eef0f4;font-weight:800;">{{ $cabinLabel }}</td></tr>
                <tr><td style="padding:10px 0;border-bottom:1px solid #eef0f4;color:{{ $muted }};">Ticket and extras</td><td align="right" style="padding:10px 0;border-bottom:1px solid #eef0f4;font-weight:800;">{{ $sym }}{{ number_format($expectedAmount, 2) }}</td></tr>
                @if($charges > 0)
                <tr><td style="padding:10px 0;color:{{ $muted }};">Gateway charges</td><td align="right" style="padding:10px 0;font-weight:800;">{{ $sym }}{{ number_format($charges, 2) }}</td></tr>
                @endif
            </table>
        </td>
    </tr>
    @if(!empty($baggageItems) || !empty($mealItems))
    <tr>
        <td style="padding:22px 30px 0;">
            <div style="font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;color:{{ $muted }};margin-bottom:10px;">Extras included</div>
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;font-size:13px;">
                @foreach($baggageItems as $bag)
                <tr><td style="padding:9px 0;border-bottom:1px solid #eef0f4;color:{{ $muted }};">{{ $bag['description'] ?? 'Baggage' }} x {{ $bag['quantity'] ?? 1 }}</td><td align="right" style="padding:9px 0;border-bottom:1px solid #eef0f4;font-weight:800;">{{ $sym }}{{ number_format((float)($bag['line_total'] ?? 0), 2) }}</td></tr>
                @endforeach
                @foreach($mealItems as $meal)
                <tr><td style="padding:9px 0;border-bottom:1px solid #eef0f4;color:{{ $muted }};">{{ $meal['description'] ?? 'Meal' }}</td><td align="right" style="padding:9px 0;border-bottom:1px solid #eef0f4;font-weight:800;">{{ $sym }}{{ number_format((float)($meal['unit_price'] ?? 0), 2) }}</td></tr>
                @endforeach
            </table>
        </td>
    </tr>
    @endif
    <tr>
        <td style="padding:22px 30px 28px;font-size:12px;line-height:1.7;color:{{ $muted }};">
            This receipt confirms payment only. Ticket confirmation or TravelFlex confirmation may arrive separately when processing is complete.
            For help, email <a href="mailto:support@travelwheel.ng" style="color:{{ $primary }};font-weight:800;text-decoration:none;">support@travelwheel.ng</a>
            or call <strong style="color:{{ $ink }};">+2348056265618</strong>.
        </td>
    </tr>
    <tr>
        <td style="background:#fbfcfe;border-top:1px solid {{ $line }};padding:16px 30px;text-align:center;color:#98a2b3;font-size:11px;line-height:1.6;">
            TravelWheel Limited<br>Receipt for booking {{ $booking->booking_ref }}.
        </td>
    </tr>
</table>
</td></tr>
</table>
</body>
</html>
