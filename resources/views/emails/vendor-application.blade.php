{{-- Shared by every vendor onboarding email. Expects $heading, $paragraphs (list of strings), and optionally $rows (label => value), $buttonUrl / $buttonLabel. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $heading }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f5f7fa; font-family:'Segoe UI', Arial, sans-serif; color:#333;">
    <table role="presentation" style="width:100%; border-collapse:collapse; background-color:#f5f7fa; padding:30px 0;">
        <tr>
            <td align="center" style="padding:30px 12px;">
                <table role="presentation" style="width:100%; max-width:650px; background:#ffffff; border-radius:10px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="background:#0D1883; padding:22px; text-align:center;">
                            <div style="color:#ffffff; font-size:13px; letter-spacing:2px; text-transform:uppercase; opacity:.85;">TravelWheel Partners</div>
                            <h2 style="color:#ffffff; font-size:20px; margin:6px 0 0;">{{ $heading }}</h2>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 30px;">
                            @foreach ($paragraphs as $paragraph)
                                <p style="font-size:15px; line-height:1.6; margin:0 0 14px;">{!! nl2br(e($paragraph)) !!}</p>
                            @endforeach

                            @if (! empty($rows))
                                <table style="width:100%; border-collapse:collapse; font-size:14px; margin:8px 0 20px; border:1px solid #ddd;">
                                    @foreach ($rows as $label => $value)
                                        <tr>
                                            <td style="padding:10px 8px; background-color:#f0f4f8; border-bottom:1px solid #ddd; width:38%; vertical-align:top;"><strong>{{ $label }}</strong></td>
                                            <td style="padding:10px 8px; border-bottom:1px solid #ddd;">{!! nl2br(e($value)) !!}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            @endif

                            @if (! empty($buttonUrl))
                                <p style="text-align:center; margin:24px 0 8px;">
                                    <a href="{{ $buttonUrl }}" style="background:#0D1883; color:#ffffff; text-decoration:none; padding:12px 28px; border-radius:30px; font-weight:600; display:inline-block;">{{ $buttonLabel }}</a>
                                </p>
                                <p style="font-size:12px; color:#888; text-align:center; margin:0 0 10px; word-break:break-all;">{{ $buttonUrl }}</p>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f0f4f8; padding:16px; text-align:center; font-size:12px; color:#777;">
                            TravelWheel &middot; {{ config('vendor_onboarding.notify_email') }} &middot; www.travelwheel.ng
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
