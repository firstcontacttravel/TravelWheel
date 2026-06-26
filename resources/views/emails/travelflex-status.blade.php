@php
    $applicant = $application->applicant_details ?? [];
    $name = $applicant['full_name'] ?? 'Traveller';
    $primary = '#303191';
    $accent = '#009933';
    $ink = '#111827';
    $muted = '#667085';
    $line = '#e6e8ee';
    $tone = match ($status) {
        'approved' => ['label' => 'Approved', 'bg' => '#eafff0', 'color' => '#007a2f', 'title' => 'Your TravelFlex application has been approved'],
        'rejected' => ['label' => 'Not approved', 'bg' => '#fff1f2', 'color' => '#be123c', 'title' => 'We have an update on your TravelFlex application'],
        default => ['label' => 'Under review', 'bg' => '#eef2ff', 'color' => $primary, 'title' => 'Your TravelFlex application is being reviewed'],
    };
    $money = fn ($amount): string => $amount === null || $amount === '' ? '-' : 'NGN ' . number_format((float) $amount, 2);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $tone['title'] }}</title>
</head>
<body style="margin:0;padding:0;background:#f8f9fc;font-family:Arial,Helvetica,sans-serif;color:{{ $ink }};">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#f8f9fc;padding:28px 0;">
<tr><td align="center" style="padding:0 12px;">
<table width="640" cellpadding="0" cellspacing="0" role="presentation" style="width:640px;max-width:100%;background:#ffffff;border:1px solid {{ $line }};border-radius:14px;overflow:hidden;">
    <tr>
        <td style="background:linear-gradient(105deg,#303191 0%,#254277 58%,#0c6b64 100%);padding:30px;color:#ffffff;">
            <div style="font-size:22px;font-weight:900;">TravelWheel</div>
            <div style="font-size:11px;font-weight:800;color:#dfffea;text-transform:uppercase;letter-spacing:.08em;margin-top:4px;">TravelFlex update</div>
            <div style="font-size:25px;font-weight:900;line-height:1.25;margin-top:24px;">{{ $tone['title'] }}</div>
            <div style="font-size:13px;line-height:1.7;color:#edf4ff;margin-top:8px;max-width:520px;">Hi {{ $name }}, here is the latest status for your TravelFlex application.</div>
        </td>
    </tr>
    <tr>
        <td style="padding:24px 30px 0;">
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:{{ $tone['bg'] }};border:1px solid {{ $line }};border-radius:12px;">
                <tr>
                    <td style="padding:18px;">
                        <div style="font-size:10px;font-weight:900;color:{{ $tone['color'] }};text-transform:uppercase;letter-spacing:.08em;">Current status</div>
                        <div style="font-size:28px;font-weight:900;color:{{ $ink }};margin-top:5px;">{{ $tone['label'] }}</div>
                    </td>
                    <td align="right" style="padding:18px;">
                        <div style="font-size:10px;font-weight:900;color:{{ $muted }};text-transform:uppercase;letter-spacing:.08em;">Booking reference</div>
                        <div style="font-size:14px;font-weight:900;color:{{ $primary }};font-family:'Courier New',monospace;margin-top:6px;">{{ $application->booking_ref ?: '-' }}</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="padding:20px 30px 0;">
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;font-size:13px;">
                <tr><td style="padding:10px 0;border-bottom:1px solid #eef0f4;color:{{ $muted }};">Down payment</td><td align="right" style="padding:10px 0;border-bottom:1px solid #eef0f4;font-weight:800;">{{ $money($application->down_payment) }}</td></tr>
                <tr><td style="padding:10px 0;border-bottom:1px solid #eef0f4;color:{{ $muted }};">Total payable</td><td align="right" style="padding:10px 0;border-bottom:1px solid #eef0f4;font-weight:800;">{{ $money($application->grand_total) }}</td></tr>
                <tr><td style="padding:10px 0;border-bottom:1px solid #eef0f4;color:{{ $muted }};">Payment status</td><td align="right" style="padding:10px 0;border-bottom:1px solid #eef0f4;font-weight:800;">{{ str((string) $application->payment_status)->replace('_', ' ')->headline() }}</td></tr>
            </table>
        </td>
    </tr>
    @if(filled($note))
    <tr>
        <td style="padding:20px 30px 0;">
            <div style="background:#fbfcfe;border:1px solid {{ $line }};border-radius:12px;padding:16px;font-size:13px;line-height:1.7;color:{{ $muted }};">
                <strong style="color:{{ $ink }};">Review note:</strong> {{ $note }}
            </div>
        </td>
    </tr>
    @endif
    <tr>
        <td style="padding:22px 30px 28px;font-size:12px;line-height:1.7;color:{{ $muted }};">
            For help, email <a href="mailto:support@travelwheel.ng" style="color:{{ $primary }};font-weight:800;text-decoration:none;">support@travelwheel.ng</a>
            or call <strong style="color:{{ $ink }};">+2348056265618</strong>.
        </td>
    </tr>
</table>
</td></tr>
</table>
</body>
</html>
