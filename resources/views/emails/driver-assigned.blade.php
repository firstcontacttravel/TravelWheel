<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Assigned</title>
</head>
<body style="margin:0; padding:0; background:#f0f2f8; font-family: 'DM Sans', Arial, sans-serif; color:#1a1a1a;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f2f8; padding:30px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.06);">

                    {{-- Header --}}
                    <tr>
                        <td style="background:linear-gradient(135deg,#0d1883,#2d39b6); padding:28px 30px;">
                            <p style="margin:0; font-size:20px; font-weight:700; color:#fff;">TravelWheel</p>
                            <p style="margin:4px 0 0; font-size:12px; color:rgba(255,255,255,0.75); letter-spacing:0.05em; text-transform:uppercase;">Ground Transport</p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:30px;">
                            <h2 style="margin:0 0 8px; font-size:20px; color:#0d1883;">Your driver has been assigned 🚗</h2>
                            <p style="margin:0 0 24px; font-size:14px; color:#555; line-height:1.6;">
                                Hi {{ $booking->full_name ?? 'there' }}, your driver and vehicle for your upcoming trip are confirmed. Details below:
                            </p>

                            {{-- Driver Info Box --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#eef1ff; border:1px solid #c5cef8; border-radius:10px; margin-bottom:20px;">
                                <tr>
                                    <td style="padding:18px 20px;">
                                        <p style="margin:0 0 4px; font-size:11px; font-weight:700; color:#0d1883; text-transform:uppercase; letter-spacing:0.05em;">Driver</p>
                                        <p style="margin:0 0 14px; font-size:16px; font-weight:600; color:#1a1a1a;">
                                            {{ $driver->name ?? 'Unknown' }}
                                            @if($driver && $driver->phone)
                                                <span style="font-size:13px; font-weight:400; color:#666;"> · {{ $driver->phone }}</span>
                                            @endif
                                        </p>

                                        <p style="margin:0 0 4px; font-size:11px; font-weight:700; color:#0d1883; text-transform:uppercase; letter-spacing:0.05em;">Vehicle</p>
                                        <p style="margin:0; font-size:14px; color:#1a1a1a;">
                                            {{ $assignment->car_model }} · {{ $assignment->car_colour }} · <strong>{{ $assignment->plate_number }}</strong>
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            {{-- Booking Reference --}}
                            @if($booking)
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
                                <tr>
                                    <td style="padding:12px 0; border-top:1px solid #e8eaf5; border-bottom:1px solid #e8eaf5;">
                                        <p style="margin:0; font-size:12px; color:#888;">Booking Reference</p>
                                        <p style="margin:2px 0 0; font-size:14px; font-weight:600; color:#0d1883; font-family:monospace;">{{ $booking->payment_reference ?? '—' }}</p>
                                    </td>
                                </tr>
                            </table>
                            @endif

                            {{-- Notes --}}
                            @if($assignment->notes)
                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#fffbea; border:1px solid #f0d96a; border-radius:8px; margin-bottom:20px;">
                                <tr>
                                    <td style="padding:12px 16px;">
                                        <p style="margin:0; font-size:11px; font-weight:700; color:#7a5c00; text-transform:uppercase;">Notes from your driver</p>
                                        <p style="margin:4px 0 0; font-size:13px; color:#5c4500;">{{ $assignment->notes }}</p>
                                    </td>
                                </tr>
                            </table>
                            @endif

                            <p style="margin:24px 0 0; font-size:13px; color:#888; line-height:1.6;">
                                If you have any questions about your trip, please reply to this email or contact our support team.
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background:#f7f8ff; padding:18px 30px; text-align:center; border-top:1px solid #e8eaf5;">
                            <p style="margin:0; font-size:11px; color:#999;">© {{ date('Y') }} TravelWheel. All rights reserved.</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>