{{-- resources/views/mail/eticket.blade.php --}}
@php
    $passengers = $passengers ?? \App\Support\FlightDisplay::passengers($booking->passengers_snapshot ?? []);
    $firstPax = collect($passengers)->first();
    $firstName = $firstPax['first_name'] ?? 'Traveller';
    $statusLabel = $isTicketed ? 'Ticketed' : 'Confirmed';
    $statusBg = $isTicketed ? '#eafff0' : '#fff7ed';
    $statusColor = $isTicketed ? '#007a2a' : '#b45309';
    $primary = '#303191';
    $accent = '#009933';
    $surface = '#f8f9fc';
    $line = '#e6e8ee';
    $ink = '#111827';
    $muted = '#667085';
    $routeText = trim((string)($booking->route ?? ''));
    $totalLabel = ($currencySymbol ?? '') . number_format((float)($totalAmount ?? $booking->total_price ?? 0), 2);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your E-Ticket - {{ $bookingRef }}</title>
</head>
<body style="margin:0;padding:0;background:{{ $surface }};font-family:Arial,Helvetica,sans-serif;color:{{ $ink }};">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:{{ $surface }};padding:28px 0;">
<tr><td align="center" style="padding:0 12px;">
<table width="640" cellpadding="0" cellspacing="0" role="presentation" style="width:640px;max-width:100%;background:#ffffff;border:1px solid {{ $line }};border-radius:14px;overflow:hidden;">
    <tr>
        <td style="background:linear-gradient(105deg,#303191 0%,#254277 56%,#0c6b64 100%);padding:28px 30px;color:#ffffff;">
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                <tr>
                    <td>
                        <div style="font-size:22px;font-weight:800;letter-spacing:-.2px;">TravelWheel</div>
                        <div style="font-size:11px;font-weight:700;color:#dfffea;text-transform:uppercase;letter-spacing:.08em;margin-top:4px;">Electronic ticket</div>
                    </td>
                    <td align="right">
                        <span style="display:inline-block;background:{{ $statusBg }};color:{{ $statusColor }};border-radius:999px;padding:7px 13px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;">{{ $statusLabel }}</span>
                    </td>
                </tr>
            </table>
            <div style="font-size:24px;font-weight:800;line-height:1.25;margin-top:24px;">
                {{ $isTicketed ? 'Your e-ticket is ready' : 'Your booking is confirmed' }}
            </div>
            <div style="font-size:13px;line-height:1.7;color:#edf4ff;margin-top:8px;max-width:520px;">
                Hi {{ $firstName }}, {{ $isTicketed ? 'your ticket has been issued and the PDF copy is attached to this email.' : 'your seat is reserved and ticketing is in progress. We will email your ticket shortly.' }}
            </div>
        </td>
    </tr>

    <tr>
        <td style="padding:24px 30px 6px;">
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border:1px solid {{ $line }};border-radius:12px;background:#fbfcfe;">
                <tr>
                    <td style="padding:18px 18px;border-right:1px solid {{ $line }};">
                        <div style="font-size:10px;font-weight:800;color:{{ $muted }};text-transform:uppercase;letter-spacing:.08em;">Booking reference</div>
                        <div style="font-size:24px;font-weight:800;color:{{ $primary }};font-family:'Courier New',monospace;margin-top:5px;">{{ $bookingRef }}</div>
                    </td>
                    <td style="padding:18px 18px;" align="right">
                        <div style="font-size:10px;font-weight:800;color:{{ $muted }};text-transform:uppercase;letter-spacing:.08em;">Trip</div>
                        <div style="font-size:14px;font-weight:800;color:{{ $ink }};margin-top:5px;">{{ $tripLabel ?? 'Flight' }}</div>
                        <div style="font-size:12px;color:{{ $muted }};margin-top:2px;">{{ $cabin ?? \App\Support\FlightDisplay::cabin($booking->flight_snapshot ?? [], $booking) }}</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <tr>
        <td style="padding:16px 30px 0;">
            <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:{{ $muted }};margin-bottom:10px;">Booking overview</div>
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;font-size:13px;">
                <tr>
                    <td style="padding:10px 0;border-bottom:1px solid #eef0f4;color:{{ $muted }};">Route</td>
                    <td align="right" style="padding:10px 0;border-bottom:1px solid #eef0f4;font-weight:800;color:{{ $ink }};">{{ $routeText ?: '-' }}</td>
                </tr>
                <tr>
                    <td style="padding:10px 0;border-bottom:1px solid #eef0f4;color:{{ $muted }};">Airline</td>
                    <td align="right" style="padding:10px 0;border-bottom:1px solid #eef0f4;font-weight:800;color:{{ $ink }};">{{ $airline ?: ($booking->airline ?? '-') }}</td>
                </tr>
                <tr>
                    <td style="padding:10px 0;border-bottom:1px solid #eef0f4;color:{{ $muted }};">PNR</td>
                    <td align="right" style="padding:10px 0;border-bottom:1px solid #eef0f4;font-weight:800;color:{{ $primary }};font-family:'Courier New',monospace;">{{ $ticketPNR ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding:10px 0;color:{{ $muted }};">Total paid</td>
                    <td align="right" style="padding:10px 0;font-size:16px;font-weight:900;color:{{ $ink }};">{{ $totalLabel }}</td>
                </tr>
            </table>
        </td>
    </tr>

    @if(!empty($passengers))
    <tr>
        <td style="padding:22px 30px 0;">
            <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:{{ $muted }};margin-bottom:10px;">Passengers</div>
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border:1px solid {{ $line }};border-collapse:collapse;font-size:12px;">
                <tr>
                    <th align="left" style="background:#fbfcfe;padding:9px 10px;color:{{ $muted }};font-size:10px;text-transform:uppercase;">Name</th>
                    <th align="left" style="background:#fbfcfe;padding:9px 10px;color:{{ $muted }};font-size:10px;text-transform:uppercase;">Type</th>
                    <th align="left" style="background:#fbfcfe;padding:9px 10px;color:{{ $muted }};font-size:10px;text-transform:uppercase;">Ticket</th>
                </tr>
                @foreach($passengers as $pax)
                <tr>
                    <td style="padding:10px;border-top:1px solid #eef0f4;font-weight:800;color:{{ $ink }};">{{ trim(($pax['title'] ?? '') . ' ' . strtoupper($pax['first_name'] ?? '') . ' ' . strtoupper($pax['last_name'] ?? '')) }}</td>
                    <td style="padding:10px;border-top:1px solid #eef0f4;color:{{ $muted }};">{{ match($pax['type'] ?? 'ADT') { 'ADT' => 'Adult', 'CHD' => 'Child', 'INF' => 'Infant', default => 'Passenger' } }}</td>
                    <td style="padding:10px;border-top:1px solid #eef0f4;font-family:'Courier New',monospace;color:{{ $primary }};font-weight:800;">{{ $pax['eticket'] ?? ($isTicketed ? '-' : 'Pending') }}</td>
                </tr>
                @endforeach
            </table>
        </td>
    </tr>
    @endif

    <tr>
        <td style="padding:22px 30px 0;">
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#f7f7ff;border:1px solid #d7d8ff;border-radius:12px;">
                <tr>
                    <td style="padding:15px 16px;">
                        <div style="font-size:13px;font-weight:900;color:{{ $primary }};">Before you travel</div>
                        <div style="font-size:12px;line-height:1.7;color:#30364a;margin-top:5px;">
                            Arrive 2 hours before domestic flights and 3 hours before international flights. Carry a valid ID or passport, and make sure the name on your document matches your ticket.
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <tr>
        <td style="padding:22px 30px 28px;">
            <div style="font-size:12px;line-height:1.7;color:{{ $muted }};">
                Need help? Email <a href="mailto:support@travelwheel.ng" style="color:{{ $primary }};font-weight:800;text-decoration:none;">support@travelwheel.ng</a>
                or call <strong style="color:{{ $ink }};">+2348056265618</strong>. Always quote your booking reference: <strong style="font-family:'Courier New',monospace;color:{{ $ink }};">{{ $bookingRef }}</strong>.
            </div>
        </td>
    </tr>

    <tr>
        <td style="background:#fbfcfe;border-top:1px solid {{ $line }};padding:16px 30px;text-align:center;color:#98a2b3;font-size:11px;line-height:1.6;">
            TravelWheel Limited<br>
            This message was sent for booking {{ $bookingRef }}.
        </td>
    </tr>
</table>
</td></tr>
</table>
</body>
</html>
