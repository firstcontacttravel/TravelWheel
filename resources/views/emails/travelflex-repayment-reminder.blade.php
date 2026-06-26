@php
    $applicant = $application->applicant_details ?? [];
    $name = $applicant['full_name'] ?? 'Traveller';
    $amount = $instalment['amount'] ?? $instalment['total'] ?? $instalment['principal'] ?? 0;
    $dueDate = $instalment['dueDate'] ?? $instalment['due_date'] ?? $instalment['date'] ?? '-';
    $label = $instalment['label'] ?? 'TravelFlex repayment';
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
<title>TravelFlex Repayment Reminder</title>
</head>
<body style="margin:0;padding:0;background:#f8f9fc;font-family:Arial,Helvetica,sans-serif;color:{{ $ink }};">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#f8f9fc;padding:28px 0;">
<tr><td align="center" style="padding:0 12px;">
<table width="640" cellpadding="0" cellspacing="0" role="presentation" style="width:640px;max-width:100%;background:#ffffff;border:1px solid {{ $line }};border-radius:14px;overflow:hidden;">
    <tr>
        <td style="background:linear-gradient(105deg,#303191 0%,#254277 58%,#0c6b64 100%);padding:30px;color:#ffffff;">
            <div style="font-size:22px;font-weight:900;">TravelWheel</div>
            <div style="font-size:11px;font-weight:800;color:#dfffea;text-transform:uppercase;letter-spacing:.08em;margin-top:4px;">TravelFlex repayment</div>
            <div style="font-size:25px;font-weight:900;line-height:1.25;margin-top:24px;">Your repayment is {{ $timing }}</div>
            <div style="font-size:13px;line-height:1.7;color:#edf4ff;margin-top:8px;max-width:520px;">Hi {{ $name }}, this is a reminder for your TravelFlex repayment on booking {{ $application->booking_ref ?: '-' }}.</div>
        </td>
    </tr>
    <tr>
        <td style="padding:24px 30px 0;">
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#eafff0;border:1px solid #bdecc9;border-radius:12px;">
                <tr>
                    <td style="padding:18px;">
                        <div style="font-size:10px;font-weight:900;color:{{ $accent }};text-transform:uppercase;letter-spacing:.08em;">Amount due</div>
                        <div style="font-size:28px;font-weight:900;color:{{ $ink }};margin-top:5px;">NGN {{ number_format((float) $amount, 2) }}</div>
                    </td>
                    <td align="right" style="padding:18px;">
                        <div style="font-size:10px;font-weight:900;color:{{ $muted }};text-transform:uppercase;letter-spacing:.08em;">Due date</div>
                        <div style="font-size:14px;font-weight:900;color:{{ $primary }};margin-top:6px;">{{ $dueDate }}</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="padding:20px 30px 0;">
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;font-size:13px;">
                <tr><td style="padding:10px 0;border-bottom:1px solid #eef0f4;color:{{ $muted }};">Instalment</td><td align="right" style="padding:10px 0;border-bottom:1px solid #eef0f4;font-weight:800;">{{ $label }}</td></tr>
                <tr><td style="padding:10px 0;border-bottom:1px solid #eef0f4;color:{{ $muted }};">Booking reference</td><td align="right" style="padding:10px 0;border-bottom:1px solid #eef0f4;font-weight:800;">{{ $application->booking_ref ?: '-' }}</td></tr>
                <tr><td style="padding:10px 0;border-bottom:1px solid #eef0f4;color:{{ $muted }};">Plan</td><td align="right" style="padding:10px 0;border-bottom:1px solid #eef0f4;font-weight:800;">{{ data_get($application->repayment_plan, 'repayment_plan', '-') }}</td></tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="padding:22px 30px 28px;font-size:12px;line-height:1.7;color:{{ $muted }};">
            Please complete this repayment using the agreed TravelFlex payment channel. For help, email
            <a href="mailto:support@travelwheel.ng" style="color:{{ $primary }};font-weight:800;text-decoration:none;">support@travelwheel.ng</a>
            or call <strong style="color:{{ $ink }};">+2348056265618</strong>.
        </td>
    </tr>
</table>
</td></tr>
</table>
</body>
</html>
