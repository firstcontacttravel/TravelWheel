@php
    $passengers = \App\Support\FlightDisplay::passengers($booking->passengers_snapshot ?? []);
    $firstPax = collect($passengers)->first();
    $firstName = $firstPax['first_name'] ?? 'Traveller';
    $cabinLabel = \App\Support\FlightDisplay::cabin($booking->flight_snapshot ?? [], $booking);
    $currency = $booking->payment_currency ?: ($booking->currency ?? 'NGN');
    $sym = match($currency) { 'NGN' => '₦', 'USD' => '$', 'GBP' => '£', 'EUR' => '€', default => $currency . ' ' };
    $expectedAmount = (float) ($booking->payment_amount ?? $booking->total_price ?? 0);
    $chargedAmount = (float) ($booking->payment_charged_amount ?? $expectedAmount);
    $extraServices = $booking->extra_services_snapshot ?? [];
    $baggageItems = $extraServices['baggage'] ?? [];
    $mealItems = $extraServices['meal'] ?? [];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Receipt - {{ $booking->booking_ref }}</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:28px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;">
    <tr>
        <td style="background:#0f766e;color:#ffffff;padding:24px 30px;">
            <div style="font-size:22px;font-weight:800;">Payment Receipt</div>
            <div style="font-size:13px;opacity:.9;margin-top:4px;">Your payment was received successfully.</div>
        </td>
    </tr>
    <tr>
        <td style="padding:26px 30px;">
            <p style="font-size:14px;line-height:1.7;margin:0 0 18px;color:#334155;">
                Hi <strong>{{ $firstName }}</strong>,<br>
                We received your payment for booking <strong>{{ $booking->booking_ref }}</strong>.
            </p>

            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:13px;">
                <tr><td style="padding:9px 0;border-bottom:1px solid #e2e8f0;color:#64748b;">Booking Reference</td><td align="right" style="padding:9px 0;border-bottom:1px solid #e2e8f0;font-weight:700;">{{ $booking->booking_ref }}</td></tr>
                <tr><td style="padding:9px 0;border-bottom:1px solid #e2e8f0;color:#64748b;">Payment Reference</td><td align="right" style="padding:9px 0;border-bottom:1px solid #e2e8f0;font-weight:700;">{{ $booking->payment_reference }}</td></tr>
                <tr><td style="padding:9px 0;border-bottom:1px solid #e2e8f0;color:#64748b;">Gateway</td><td align="right" style="padding:9px 0;border-bottom:1px solid #e2e8f0;font-weight:700;">{{ ucfirst($booking->payment_gateway ?? 'SeerBit') }}</td></tr>
                <tr><td style="padding:9px 0;border-bottom:1px solid #e2e8f0;color:#64748b;">Payment Date</td><td align="right" style="padding:9px 0;border-bottom:1px solid #e2e8f0;font-weight:700;">{{ optional($booking->payment_verified_at)->format('d M Y, H:i') }}</td></tr>
                <tr><td style="padding:9px 0;border-bottom:1px solid #e2e8f0;color:#64748b;">Route</td><td align="right" style="padding:9px 0;border-bottom:1px solid #e2e8f0;font-weight:700;">{{ $booking->route }}</td></tr>
                <tr><td style="padding:9px 0;border-bottom:1px solid #e2e8f0;color:#64748b;">Cabin</td><td align="right" style="padding:9px 0;border-bottom:1px solid #e2e8f0;font-weight:700;">{{ $cabinLabel }}</td></tr>
                <tr><td style="padding:9px 0;border-bottom:1px solid #e2e8f0;color:#64748b;">Ticket and Extras</td><td align="right" style="padding:9px 0;border-bottom:1px solid #e2e8f0;font-weight:700;">{{ $sym }}{{ number_format($expectedAmount, 2) }}</td></tr>
                @if($chargedAmount > $expectedAmount)
                <tr><td style="padding:9px 0;border-bottom:1px solid #e2e8f0;color:#64748b;">Gateway Charges</td><td align="right" style="padding:9px 0;border-bottom:1px solid #e2e8f0;font-weight:700;">{{ $sym }}{{ number_format($chargedAmount - $expectedAmount, 2) }}</td></tr>
                @endif
            </table>

            @if(!empty($passengers))
            <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin:24px 0 10px;">Passengers</div>
            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:13px;border:1px solid #e2e8f0;">
                <tr>
                    <th align="left" style="background:#f8fafc;padding:8px 10px;border-bottom:1px solid #e2e8f0;color:#94a3b8;font-size:11px;">#</th>
                    <th align="left" style="background:#f8fafc;padding:8px 10px;border-bottom:1px solid #e2e8f0;color:#94a3b8;font-size:11px;">Name</th>
                    <th align="left" style="background:#f8fafc;padding:8px 10px;border-bottom:1px solid #e2e8f0;color:#94a3b8;font-size:11px;">Type</th>
                    <th align="left" style="background:#f8fafc;padding:8px 10px;border-bottom:1px solid #e2e8f0;color:#94a3b8;font-size:11px;">Passport</th>
                </tr>
                @foreach($passengers as $i => $pax)
                <tr>
                    <td style="padding:9px 10px;border-bottom:1px solid #f1f5f9;">{{ $i + 1 }}</td>
                    <td style="padding:9px 10px;border-bottom:1px solid #f1f5f9;font-weight:700;">{{ $pax['title'] ?? '' }} {{ strtoupper($pax['first_name'] ?? '') }} {{ strtoupper($pax['last_name'] ?? '') }}</td>
                    <td style="padding:9px 10px;border-bottom:1px solid #f1f5f9;">{{ match($pax['type'] ?? 'ADT') { 'ADT' => 'Adult', 'CHD' => 'Child', 'INF' => 'Infant', default => 'Pax' } }}</td>
                    <td style="padding:9px 10px;border-bottom:1px solid #f1f5f9;font-family:'Courier New',monospace;">{{ $pax['passport_no'] ?? '-' }}</td>
                </tr>
                @endforeach
            </table>
            @endif

            @if(!empty($baggageItems) || !empty($mealItems))
            <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin:24px 0 10px;">Extra Services Included</div>
            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:13px;">
                @foreach($baggageItems as $bag)
                <tr><td style="padding:7px 0;border-bottom:1px solid #f1f5f9;">{{ $bag['description'] ?? 'Baggage' }} x {{ $bag['quantity'] ?? 1 }}</td><td align="right" style="padding:7px 0;border-bottom:1px solid #f1f5f9;font-weight:700;">{{ $sym }}{{ number_format((float)($bag['line_total'] ?? 0), 2) }}</td></tr>
                @endforeach
                @foreach($mealItems as $meal)
                <tr><td style="padding:7px 0;border-bottom:1px solid #f1f5f9;">{{ $meal['description'] ?? 'Meal' }}</td><td align="right" style="padding:7px 0;border-bottom:1px solid #f1f5f9;font-weight:700;">{{ $sym }}{{ number_format((float)($meal['unit_price'] ?? 0), 2) }}</td></tr>
                @endforeach
            </table>
            @endif

            <div style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:10px;padding:16px 18px;margin-top:22px;">
                <table width="100%"><tr>
                    <td style="font-size:14px;font-weight:800;color:#065f46;">Total Charged</td>
                    <td align="right" style="font-size:22px;font-weight:800;color:#065f46;font-family:'Courier New',monospace;">{{ $sym }}{{ number_format($chargedAmount, 2) }}</td>
                </tr></table>
            </div>

            <p style="font-size:12.5px;line-height:1.7;color:#64748b;margin:22px 0 0;">
                This receipt confirms payment only. Your ticket confirmation or TravelFlex confirmation will be sent separately when processing is complete.
            </p>
        </td>
    </tr>
    <tr>
        <td style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:16px 30px;text-align:center;font-size:12px;color:#94a3b8;">
            &copy; {{ date('Y') }} TravelWheel. All rights reserved.
        </td>
    </tr>
</table>
</td></tr>
</table>
</body>
</html>
