<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank you for booking with TravelWheel</title>
</head>
<body style="margin:0; padding:0; background:#f4f6f9; font-family:'Segoe UI', Arial, sans-serif; color:#1a1a1a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;">
    <tr>
        <td align="center" style="padding:28px 12px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px; background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.07);">

                <tr>
                    <td style="background:#0d1883; padding:30px 32px; text-align:center;">
                        <div style="font-size:34px; line-height:1;">&#128591;</div>
                        <h1 style="color:#ffffff; font-size:22px; margin:12px 0 4px; font-weight:700;">Thank you for choosing TravelWheel</h1>
                        <p style="color:rgba(255,255,255,0.8); font-size:14px; margin:0;">We truly appreciate your booking</p>
                    </td>
                </tr>

                <tr>
                    <td style="padding:28px 32px 8px;">
                        <p style="font-size:15px; line-height:1.6; margin:0 0 14px;">{{ $name ? "Dear $name," : 'Hello,' }}</p>
                        <p style="font-size:15px; line-height:1.6; margin:0 0 14px;">
                            Thank you for booking <strong>{{ $bookedProduct }}</strong> with us. Your booking details have
                            been sent to you in a separate email. Our reservations team is here to make sure everything goes smoothly;
                            if you need anything, simply reply to this email.
                        </p>
                        <p style="font-size:15px; line-height:1.6; margin:0;">
                            Planning more travel? TravelWheel has everything you need for the rest of your journey:
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="padding:16px 24px 8px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                            @foreach ($suggestions as $item)
                                <tr>
                                    <td style="padding:6px 8px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f7f8ff; border:1px solid #e0e4f8; border-radius:10px;">
                                            <tr>
                                                @if ($item['icon'])
                                                    <td width="56" valign="middle" style="padding:12px 0 12px 14px;">
                                                        <img src="{{ $item['icon'] }}" width="40" height="40" alt="" style="display:block; width:40px; height:40px;">
                                                    </td>
                                                @endif
                                                <td valign="middle" style="padding:12px 14px;">
                                                    <a href="{{ $item['url'] }}" style="color:#0d1883; font-size:15px; font-weight:700; text-decoration:none;">{{ $item['name'] }}</a>
                                                    <div style="color:#5c6070; font-size:13px; line-height:1.45; margin-top:2px;">{{ $item['pitch'] }}</div>
                                                </td>
                                                <td width="96" valign="middle" align="right" style="padding:12px 14px 12px 0;">
                                                    <a href="{{ $item['url'] }}" style="display:inline-block; background:#0d9c53; color:#ffffff; font-size:13px; font-weight:600; text-decoration:none; padding:8px 14px; border-radius:20px; white-space:nowrap;">Book now</a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:16px 32px 28px;">
                        <p style="font-size:15px; line-height:1.6; margin:0 0 4px;">We look forward to serving you again.</p>
                        <p style="font-size:15px; line-height:1.6; margin:0;"><strong>The TravelWheel Reservations Team</strong></p>
                    </td>
                </tr>

                <tr>
                    <td style="background:#f7f8ff; padding:18px 32px; text-align:center; border-top:1px solid #e8eaf5;">
                        <p style="font-size:12px; color:#888; margin:0 0 4px;">
                            <a href="{{ url('/') }}" style="color:#0d1883; text-decoration:none;">www.travelwheel.ng</a>
                            &middot; {{ config('travelwheel.reservations_email') }}
                        </p>
                        <p style="font-size:11px; color:#aaa; margin:0;">You are receiving this because you made a booking on TravelWheel.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
