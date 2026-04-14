{{-- resources/views/mail/eticket.blade.php --}}
{{-- Email-safe HTML — tables, inline styles, no external CSS --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your E-Ticket — {{ $bookingRef }}</title>
</head>
<body style="margin:0;padding:0;background:#F1F5F9;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#1E293B;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#F1F5F9;padding:24px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">

    {{-- Header --}}
    <tr>
        <td style="background:#0F172A;padding:20px 28px;">
            <table width="100%"><tr>
                <td>
                    <div style="font-size:22px;font-weight:700;color:#ffffff;">TravelWheel</div>
                    <div style="font-size:9px;font-weight:700;color:#0D9488;text-transform:uppercase;letter-spacing:1px;margin-top:3px;">Electronic Ticket</div>
                </td>
                <td align="right" style="vertical-align:middle;">
                    @if($isTicketed)
                    <span style="background:#059669;color:#fff;font-size:9px;font-weight:700;padding:5px 14px;border-radius:20px;text-transform:uppercase;">&#10003; Ticketed</span>
                    @else
                    <span style="background:#D97706;color:#fff;font-size:9px;font-weight:700;padding:5px 14px;border-radius:20px;text-transform:uppercase;">&#9203; Confirmed</span>
                    @endif
                </td>
            </tr></table>
        </td>
    </tr>

    {{-- Greeting --}}
    <tr>
        <td style="padding:24px 28px 0;">
            <p style="font-size:16px;font-weight:700;margin-bottom:8px;color:#0F172A;">
                @if($isTicketed)
                    Your e-ticket is ready! &#9992;
                @else
                    Your booking is confirmed! &#128338;
                @endif
            </p>
            <p style="font-size:13px;color:#475569;line-height:1.6;margin-bottom:0;">
                @if($isTicketed)
                    Great news! Your booking is confirmed and your e-ticket has been issued.
                    Your ticket PDF is attached to this email — please keep it safe and
                    present it at the check-in counter along with a valid photo ID.
                @else
                    Your booking is confirmed and your seat is reserved. Your e-ticket is
                    being processed and will be sent to you shortly (usually within 15–30 minutes).
                @endif
            </p>
        </td>
    </tr>

    {{-- Booking ref box --}}
    <tr>
        <td style="padding:16px 28px;">
            <table width="100%" style="background:#F0FDF4;border:1.5px solid #BBF7D0;border-radius:8px;padding:14px 18px;">
                <tr>
                    <td>
                        <div style="font-size:9px;font-weight:700;color:#059669;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Booking Reference</div>
                        <div style="font-size:24px;font-weight:700;color:#0F172A;font-family:Courier New,monospace;">{{ $bookingRef }}</div>
                    </td>
                    <td align="right" style="vertical-align:middle;">
                        <div style="font-size:11px;color:#64748B;">
                            {{ $booking->flight_data['tripLabel'] ?? 'Flight' }}<br>
                            {{ $booking->flight_data['airline'] ?? '' }}<br>
                            {{ $booking->flight_data['cabin'] ?? 'Economy' }}
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- Attachment notice --}}
    @if($isTicketed)
    <tr>
        <td style="padding:0 28px 16px;">
            <table width="100%" style="background:#EEF2FF;border:1px solid #C7D2FE;border-radius:8px;padding:12px 16px;">
                <tr>
                    <td>
                        <span style="font-size:18px;">&#127915;</span>
                    </td>
                    <td style="padding-left:10px;">
                        <div style="font-size:12px;font-weight:700;color:#4F46E5;margin-bottom:2px;">E-Ticket PDF Attached</div>
                        <div style="font-size:11px;color:#64748B;">
                            Your full e-ticket is attached as a PDF. Print it or save it to your phone for check-in.
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    @endif

    {{-- Divider --}}
    <tr><td style="padding:0 28px;"><hr style="border:none;border-top:1px solid #E2E8F0;"></td></tr>

    {{-- Reminders --}}
    <tr>
        <td style="padding:16px 28px 0;">
            <div style="font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">Important Reminders</div>
            <table width="100%" cellspacing="0" cellpadding="0">
                @foreach([
                    ['&#9992;', 'Check-in', 'Arrive 2 hrs before domestic, 3 hrs before international flights.'],
                    ['&#128282;', 'Valid ID', 'Carry a valid photo ID or passport. Names must match your ticket exactly.'],
                    ['&#128123;', 'Baggage', 'Check your airline's baggage allowance. Excess fees apply at the airport.'],
                    ['&#128241;', 'Online Check-in', 'Opens 24–48 hours before departure on your airline's website.'],
                ] as [$icon, $title, $text])
                <tr>
                    <td style="padding:6px 0;vertical-align:top;width:24px;font-size:16px;">{{ $icon }}</td>
                    <td style="padding:6px 0 6px 8px;">
                        <strong style="font-size:12px;color:#0F172A;">{{ $title }}:</strong>
                        <span style="font-size:12px;color:#475569;"> {{ $text }}</span>
                    </td>
                </tr>
                @endforeach
            </table>
        </td>
    </tr>

    {{-- Support --}}
    <tr>
        <td style="padding:16px 28px 24px;">
            <div style="font-size:11px;color:#64748B;line-height:1.7;">
                Need help? Contact us at
                <a href="mailto:support@travelwheel.com" style="color:#2563EB;font-weight:600;">support@travelwheel.com</a>
                or call <strong>+234 800 000 0000</strong> (Mon–Fri 8am–6pm).<br>
                Always quote your booking reference: <strong style="font-family:Courier New,monospace;">{{ $bookingRef }}</strong>
            </div>
        </td>
    </tr>

    {{-- Footer --}}
    <tr>
        <td style="background:#F8FAFC;border-top:1px solid #E2E8F0;padding:14px 28px;text-align:center;">
            <div style="font-size:10px;color:#94A3B8;line-height:1.6;">
                This email was sent by TravelWheel on behalf of {{ $booking->flight_data['airline'] ?? 'the operating airline' }}.<br>
                &copy; {{ date('Y') }} TravelWheel. All rights reserved.
            </div>
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>
