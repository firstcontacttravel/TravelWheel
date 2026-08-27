@php
    $flight = $booking->flight_snapshot ?? [];
    $segments = $flight['segments'] ?? [];
    $first = $segments[0] ?? [];
    $last = $segments ? $segments[array_key_last($segments)] : [];
    $route = trim(($first['from'] ?? '').' to '.($last['to'] ?? ''), ' to');
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
<title>Your Ticket Has Been Booked</title>
</head>
<body style="margin:0;padding:0;background:#f8f9fc;font-family:Arial,Helvetica,sans-serif;color:{{ $ink }};">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#f8f9fc;padding:28px 0;">
<tr><td align="center" style="padding:0 12px;">
<table width="640" cellpadding="0" cellspacing="0" role="presentation" style="width:640px;max-width:100%;background:#ffffff;border:1px solid {{ $line }};border-radius:14px;overflow:hidden;">
    <tr>
        <td style="background:linear-gradient(105deg,#303191 0%,#254277 58%,#0c6b64 100%);padding:30px;color:#ffffff;">
            <div style="font-size:22px;font-weight:900;">TravelWheel</div>
            <div style="font-size:11px;font-weight:800;color:#dfffea;text-transform:uppercase;letter-spacing:.08em;margin-top:4px;">TravelFlex</div>
            <div style="font-size:25px;font-weight:900;line-height:1.25;margin-top:24px;">Your ticket has been booked</div>
            <div style="font-size:13px;line-height:1.7;color:#edf4ff;margin-top:8px;max-width:520px;">Good news — your flight is booked and your TravelFlex repayment plan stays active as scheduled.</div>
        </td>
    </tr>
    <tr>
        <td style="padding:24px 30px 0;">
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#eafff0;border:1px solid {{ $line }};border-radius:12px;">
                <tr>
                    <td style="padding:18px;">
                        <div style="font-size:10px;font-weight:900;color:{{ $accent }};text-transform:uppercase;letter-spacing:.08em;">Status</div>
                        <div style="font-size:22px;font-weight:900;color:{{ $ink }};margin-top:5px;">Ticket Booked</div>
                    </td>
                    <td align="right" style="padding:18px;">
                        <div style="font-size:10px;font-weight:900;color:{{ $muted }};text-transform:uppercase;letter-spacing:.08em;">Booking reference</div>
                        <div style="font-size:14px;font-weight:900;color:{{ $primary }};font-family:'Courier New',monospace;margin-top:6px;">{{ $booking->booking_ref ?: '-' }}</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="padding:20px 30px 0;">
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;font-size:13px;">
                <tr><td style="padding:10px 0;border-bottom:1px solid #eef0f4;color:{{ $muted }};">Route</td><td align="right" style="padding:10px 0;border-bottom:1px solid #eef0f4;font-weight:800;">{{ $route ?: '-' }}</td></tr>
                @if(!empty($first['departDT']))
                <tr><td style="padding:10px 0;border-bottom:1px solid #eef0f4;color:{{ $muted }};">Travel date</td><td align="right" style="padding:10px 0;border-bottom:1px solid #eef0f4;font-weight:800;">{{ \Carbon\Carbon::parse($first['departDT'])->format('D, d M Y') }}</td></tr>
                @endif
                <tr><td style="padding:10px 0;color:{{ $muted }};">Airline</td><td align="right" style="padding:10px 0;font-weight:800;">{{ $flight['airline'] ?? $booking->airline ?? '-' }}</td></tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="padding:20px 30px 0;">
            <div style="background:#fbfcfe;border:1px solid {{ $line }};border-radius:12px;padding:16px;font-size:13px;line-height:1.7;color:{{ $muted }};">
                Your full itinerary and e-ticket details are held with our ticketing team. If you need a copy before your travel date, contact us with your booking reference and we'll send it across.
            </div>
        </td>
    </tr>
    <tr>
        <td style="padding:22px 30px 28px;font-size:12px;line-height:1.7;color:{{ $muted }};">
            For help, email
            <a href="mailto:support@travelwheel.ng" style="color:{{ $primary }};font-weight:800;text-decoration:none;">support@travelwheel.ng</a>
            or call <strong style="color:{{ $ink }};">+2348056265618</strong>.
        </td>
    </tr>
</table>
</td></tr>
</table>
</body>
</html>
