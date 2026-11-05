<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete your TravelWheel booking</title>
</head>
<body style="margin:0; padding:0; background:#f4f6f9; font-family:'Segoe UI', Arial, sans-serif; color:#1a1a1a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;">
    <tr>
        <td align="center" style="padding:28px 12px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background:#ffffff; border-radius:16px; overflow:hidden;">
                <tr>
                    <td style="background:#0d1883; padding:26px 32px; text-align:center;">
                        <h1 style="color:#ffffff; font-size:21px; margin:0; font-weight:700;">Complete your booking</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding:26px 32px;">
                        <p style="font-size:15px; line-height:1.6; margin:0 0 14px;">Dear {{ $booking->full_name }},</p>
                        <p style="font-size:15px; line-height:1.6; margin:0 0 18px;">Our team has made a <strong>{{ $product }}</strong> booking for you. Your booking is confirmed once payment is received.</p>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f7f8ff; border:1px solid #e0e4f8; border-radius:10px; margin-bottom:20px;">
                            <tr><td style="padding:12px 16px; font-size:14px; color:#5c6070;">Reference</td><td align="right" style="padding:12px 16px; font-size:14px; font-weight:600;">{{ $booking->payment_reference }}</td></tr>
                            @foreach ($summary as $label => $value)
                                <tr><td style="padding:0 16px 12px; font-size:14px; color:#5c6070;">{{ $label }}</td><td align="right" style="padding:0 16px 12px; font-size:14px;">{{ $value }}</td></tr>
                            @endforeach
                            <tr><td style="padding:0 16px 12px; font-size:14px; color:#5c6070;">Amount</td><td align="right" style="padding:0 16px 12px; font-size:16px; font-weight:700; color:#0d1883;">&#8358;{{ number_format((float) $total) }}</td></tr>
                        </table>
                        <p style="text-align:center; margin:0 0 18px;">
                            <a href="{{ $payUrl }}" style="display:inline-block; background:#0d9c53; color:#ffffff; font-size:15px; font-weight:600; text-decoration:none; padding:12px 28px; border-radius:24px;">Pay now</a>
                        </p>
                        <p style="font-size:13px; line-height:1.6; color:#5c6070; margin:0;">If you weren't expecting this, simply reply to this email.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
