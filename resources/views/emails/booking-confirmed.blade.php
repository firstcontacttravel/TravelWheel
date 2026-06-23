{{-- resources/views/emails/booking-confirmed.blade.php --}}
@php
    $passengers = \App\Support\FlightDisplay::passengers($booking->passengers_snapshot ?? []);
    $cabinLabel = \App\Support\FlightDisplay::cabin($booking->flight_snapshot ?? [], $booking);
    $firstPax = collect($passengers)->first();
    $firstName = $firstPax['first_name'] ?? 'Traveller';
    $currency = $booking->currency ?? 'NGN';
    $sym = match($currency) { 'NGN' => 'NGN ', 'USD' => '$', 'GBP' => 'GBP ', 'EUR' => 'EUR ', default => $currency . ' ' };
    $price = $sym . number_format((float)($booking->total_price ?? 0), 2);
    $customerInfos = collect(data_get($tripDetails ?? [], 'ItineraryInfo.CustomerInfos', []))->map(fn ($c) => $c['CustomerInfo'] ?? $c);
    $hasPdfAttachment = !empty($tripDetails ?? []);
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
<title>Booking Confirmed - {{ $booking->booking_ref }}</title>
</head>
<body style="margin:0;padding:0;background:#f8f9fc;font-family:Arial,Helvetica,sans-serif;color:{{ $ink }};">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#f8f9fc;padding:28px 0;">
<tr><td align="center" style="padding:0 12px;">
<table width="640" cellpadding="0" cellspacing="0" role="presentation" style="width:640px;max-width:100%;background:#ffffff;border:1px solid {{ $line }};border-radius:14px;overflow:hidden;">
    <tr>
        <td style="background:linear-gradient(105deg,#303191 0%,#254277 56%,#0c6b64 100%);padding:30px;color:#ffffff;">
            <div style="font-size:22px;font-weight:900;">TravelWheel</div>
            <div style="font-size:11px;font-weight:800;color:#dfffea;text-transform:uppercase;letter-spacing:.08em;margin-top:4px;">Booking confirmed</div>
            <div style="font-size:25px;font-weight:900;line-height:1.25;margin-top:24px;">Your e-ticket is ready</div>
            <div style="font-size:13px;line-height:1.7;color:#edf4ff;margin-top:8px;max-width:520px;">
                Hi {{ $firstName }}, your booking is confirmed and your ticket has been issued. {{ $hasPdfAttachment ? 'The PDF e-ticket is attached to this email.' : 'Your PDF e-ticket will be sent as soon as final ticket data is available.' }}
            </div>
        </td>
    </tr>
    <tr>
        <td style="padding:24px 30px 0;">
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border:1px solid {{ $line }};border-radius:12px;background:#fbfcfe;">
                <tr>
                    <td style="padding:18px;border-right:1px solid {{ $line }};">
                        <div style="font-size:10px;font-weight:900;color:{{ $muted }};text-transform:uppercase;letter-spacing:.08em;">Booking reference</div>
                        <div style="font-size:24px;font-weight:900;color:{{ $primary }};font-family:'Courier New',monospace;margin-top:5px;">{{ $booking->booking_ref }}</div>
                    </td>
                    <td style="padding:18px;" align="right">
                        <div style="font-size:10px;font-weight:900;color:{{ $muted }};text-transform:uppercase;letter-spacing:.08em;">Total paid</div>
                        <div style="font-size:20px;font-weight:900;color:{{ $ink }};margin-top:5px;">{{ $price }}</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="padding:20px 30px 0;">
            <div style="font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;color:{{ $muted }};margin-bottom:10px;">Flight details</div>
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;font-size:13px;">
                <tr><td style="padding:10px 0;border-bottom:1px solid #eef0f4;color:{{ $muted }};">Route</td><td align="right" style="padding:10px 0;border-bottom:1px solid #eef0f4;font-weight:800;">{{ $booking->route ?: '-' }}</td></tr>
                <tr><td style="padding:10px 0;border-bottom:1px solid #eef0f4;color:{{ $muted }};">Airline</td><td align="right" style="padding:10px 0;border-bottom:1px solid #eef0f4;font-weight:800;">{{ $booking->airline ?: '-' }}</td></tr>
                <tr><td style="padding:10px 0;border-bottom:1px solid #eef0f4;color:{{ $muted }};">Cabin</td><td align="right" style="padding:10px 0;border-bottom:1px solid #eef0f4;font-weight:800;">{{ $cabinLabel }}</td></tr>
                <tr><td style="padding:10px 0;color:{{ $muted }};">Payment method</td><td align="right" style="padding:10px 0;font-weight:800;">{{ ucfirst(str_replace('_', ' ', $booking->payment_method ?? '')) }}</td></tr>
            </table>
        </td>
    </tr>
    @if(!empty($passengers))
    <tr>
        <td style="padding:22px 30px 0;">
            <div style="font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;color:{{ $muted }};margin-bottom:10px;">Passengers</div>
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border:1px solid {{ $line }};border-collapse:collapse;font-size:12px;">
                <tr>
                    <th align="left" style="background:#fbfcfe;padding:9px 10px;color:{{ $muted }};font-size:10px;text-transform:uppercase;">Name</th>
                    <th align="left" style="background:#fbfcfe;padding:9px 10px;color:{{ $muted }};font-size:10px;text-transform:uppercase;">Type</th>
                    <th align="left" style="background:#fbfcfe;padding:9px 10px;color:{{ $muted }};font-size:10px;text-transform:uppercase;">E-ticket</th>
                </tr>
                @foreach($passengers as $i => $pax)
                    @php $ticket = data_get($customerInfos->get($i, []), 'eTicketNumber') ?: ($pax['eticket'] ?? '-'); @endphp
                    <tr>
                        <td style="padding:10px;border-top:1px solid #eef0f4;font-weight:800;">{{ trim(($pax['title'] ?? '') . ' ' . strtoupper($pax['first_name'] ?? '') . ' ' . strtoupper($pax['last_name'] ?? '')) }}</td>
                        <td style="padding:10px;border-top:1px solid #eef0f4;color:{{ $muted }};">{{ match($pax['type'] ?? 'ADT') { 'ADT' => 'Adult', 'CHD' => 'Child', 'INF' => 'Infant', default => 'Passenger' } }}</td>
                        <td style="padding:10px;border-top:1px solid #eef0f4;font-family:'Courier New',monospace;color:{{ $primary }};font-weight:800;">{{ $ticket }}</td>
                    </tr>
                @endforeach
            </table>
        </td>
    </tr>
    @endif
    <tr>
        <td style="padding:22px 30px 0;">
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#eafff0;border:1px solid #bdecc9;border-radius:12px;">
                <tr>
                    <td style="padding:15px 16px;">
                        <div style="font-size:13px;font-weight:900;color:{{ $accent }};">{{ $hasPdfAttachment ? 'PDF e-ticket attached' : 'E-ticket issued' }}</div>
                        <div style="font-size:12px;line-height:1.7;color:#245b34;margin-top:5px;">{{ $hasPdfAttachment ? 'Save it to your phone or print a copy. Present it at airport check-in with a valid ID or passport.' : 'Your booking is ticketed. If the PDF attachment is not present, our team will resend it once final airline data is available.' }}</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="padding:22px 30px 28px;font-size:12px;line-height:1.7;color:{{ $muted }};">
            Need help? Email <a href="mailto:support@travelwheel.ng" style="color:{{ $primary }};font-weight:800;text-decoration:none;">support@travelwheel.ng</a>
            or call <strong style="color:{{ $ink }};">+2348056265618</strong>. Quote <strong style="font-family:'Courier New',monospace;color:{{ $ink }};">{{ $booking->booking_ref }}</strong> when contacting support.
        </td>
    </tr>
    <tr>
        <td style="background:#fbfcfe;border-top:1px solid {{ $line }};padding:16px 30px;text-align:center;color:#98a2b3;font-size:11px;line-height:1.6;">
            TravelWheel Limited<br>This email was sent for booking {{ $booking->booking_ref }}.
        </td>
    </tr>
</table>
</td></tr>
</table>
</body>
</html>
