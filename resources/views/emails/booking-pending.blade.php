{{-- resources/views/emails/booking-pending.blade.php --}}
@php
    $passengers = \App\Support\FlightDisplay::passengers($booking->passengers_snapshot ?? []);
    $cabinLabel = \App\Support\FlightDisplay::cabin($booking->flight_snapshot ?? [], $booking);
    $firstPax = collect($passengers)->first();
    $firstName = $firstPax['first_name'] ?? 'Traveller';
    $currency = $booking->currency ?? 'NGN';
    $sym = match($currency) { 'NGN' => 'NGN ', 'USD' => '$', 'GBP' => 'GBP ', 'EUR' => 'EUR ', default => $currency . ' ' };
    $price = $sym . number_format((float)($booking->total_price ?? 0), 2);
    $resumePaymentUrl = $resumePaymentUrl ?? null;
    $paymentMethod = $paymentMethod ?? ($method ?? 'bank_transfer');
    $isHoldNotice = $isHoldNotice ?? $paymentMethod === 'hold';
    $isBankTransferNotice = $isBankTransferNotice ?? $paymentMethod === 'bank_transfer';
    $headline = $isHoldNotice ? 'Your booking is on hold' : 'Your payment is being verified';
    $subhead = $isHoldNotice ? 'Complete payment before the hold expires' : 'Our team is reviewing your transfer';
    $intro = $isHoldNotice
        ? 'Your seat is reserved with the airline while payment is pending. Complete payment before the deadline to keep this fare.'
        : 'We received your payment notification. Your booking remains on hold while our team confirms the transfer.';
    $tktFmt = null;
    $tktHours = null;
    if ($booking->tkt_time_limit) {
        try {
            $deadline = \Carbon\Carbon::parse($booking->tkt_time_limit);
            $tktFmt = $deadline->timezone('Africa/Lagos')->format('D, d M Y \a\t H:i');
            $tktHours = max(0, (int) now()->diffInHours($deadline, false));
        } catch (\Throwable) {
            $tktFmt = null;
        }
    }
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
<title>Booking Pending - {{ $booking->booking_ref }}</title>
</head>
<body style="margin:0;padding:0;background:#f8f9fc;font-family:Arial,Helvetica,sans-serif;color:{{ $ink }};">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#f8f9fc;padding:28px 0;">
<tr><td align="center" style="padding:0 12px;">
<table width="640" cellpadding="0" cellspacing="0" role="presentation" style="width:640px;max-width:100%;background:#ffffff;border:1px solid {{ $line }};border-radius:14px;overflow:hidden;">
    <tr>
        <td style="background:linear-gradient(105deg,#303191 0%,#254277 56%,#0c6b64 100%);padding:30px;color:#ffffff;">
            <div style="font-size:22px;font-weight:900;">TravelWheel</div>
            <div style="font-size:11px;font-weight:800;color:#dfffea;text-transform:uppercase;letter-spacing:.08em;margin-top:4px;">Booking pending</div>
            <div style="font-size:25px;font-weight:900;line-height:1.25;margin-top:24px;">{{ $headline }}</div>
            <div style="font-size:13px;line-height:1.7;color:#edf4ff;margin-top:8px;max-width:520px;">
                Hi {{ $firstName }}, {{ $intro }}
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
                        <div style="font-size:10px;font-weight:900;color:{{ $muted }};text-transform:uppercase;letter-spacing:.08em;">Amount due</div>
                        <div style="font-size:20px;font-weight:900;color:{{ $ink }};margin-top:5px;">{{ $price }}</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    @if($tktFmt)
    <tr>
        <td style="padding:18px 30px 0;">
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;">
                <tr>
                    <td style="padding:15px 16px;">
                        <div style="font-size:13px;font-weight:900;color:#b45309;">Hold deadline</div>
                        <div style="font-size:12px;line-height:1.7;color:#7c2d12;margin-top:5px;">
                            Your booking hold expires on <strong>{{ $tktFmt }}</strong>@if($tktHours !== null) ({{ $tktHours }} hour{{ $tktHours === 1 ? '' : 's' }} remaining)@endif.
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    @endif
    <tr>
        <td style="padding:20px 30px 0;">
            <div style="font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;color:{{ $muted }};margin-bottom:10px;">Booking summary</div>
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;font-size:13px;">
                <tr><td style="padding:10px 0;border-bottom:1px solid #eef0f4;color:{{ $muted }};">Route</td><td align="right" style="padding:10px 0;border-bottom:1px solid #eef0f4;font-weight:800;">{{ $booking->route ?: '-' }}</td></tr>
                <tr><td style="padding:10px 0;border-bottom:1px solid #eef0f4;color:{{ $muted }};">Airline</td><td align="right" style="padding:10px 0;border-bottom:1px solid #eef0f4;font-weight:800;">{{ $booking->airline ?: '-' }}</td></tr>
                <tr><td style="padding:10px 0;border-bottom:1px solid #eef0f4;color:{{ $muted }};">Cabin</td><td align="right" style="padding:10px 0;border-bottom:1px solid #eef0f4;font-weight:800;">{{ $cabinLabel }}</td></tr>
                <tr><td style="padding:10px 0;color:{{ $muted }};">Payment option</td><td align="right" style="padding:10px 0;font-weight:800;">{{ $isBankTransferNotice ? 'Bank transfer' : 'Online or bank transfer' }}</td></tr>
            </table>
        </td>
    </tr>
    @if($resumePaymentUrl)
    <tr>
        <td align="center" style="padding:24px 30px 0;">
            <a href="{{ $resumePaymentUrl }}" style="display:inline-block;background:{{ $primary }};color:#ffffff;text-decoration:none;border-radius:10px;padding:14px 22px;font-size:13px;font-weight:900;">Continue payment</a>
        </td>
    </tr>
    @endif
    <tr>
        <td style="padding:24px 30px 0;">
            <div style="font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;color:{{ $muted }};margin-bottom:12px;">What happens next</div>
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                <tr>
                    <td width="30" valign="top"><div style="width:24px;height:24px;border-radius:999px;background:{{ $accent }};color:#fff;text-align:center;line-height:24px;font-size:12px;font-weight:900;">1</div></td>
                    <td style="padding:0 0 14px 8px;"><div style="font-size:13px;font-weight:900;">Booking held</div><div style="font-size:12px;color:{{ $muted }};line-height:1.6;">Your seats are reserved with the airline.</div></td>
                </tr>
                <tr>
                    <td width="30" valign="top"><div style="width:24px;height:24px;border-radius:999px;background:#d97706;color:#fff;text-align:center;line-height:24px;font-size:12px;font-weight:900;">2</div></td>
                    <td style="padding:0 0 14px 8px;"><div style="font-size:13px;font-weight:900;">Payment review</div><div style="font-size:12px;color:{{ $muted }};line-height:1.6;">Complete payment or wait for our team to confirm your transfer.</div></td>
                </tr>
                <tr>
                    <td width="30" valign="top"><div style="width:24px;height:24px;border-radius:999px;background:#98a2b3;color:#fff;text-align:center;line-height:24px;font-size:12px;font-weight:900;">3</div></td>
                    <td style="padding:0 0 2px 8px;"><div style="font-size:13px;font-weight:900;">Ticket issued</div><div style="font-size:12px;color:{{ $muted }};line-height:1.6;">Your e-ticket will be sent to {{ $booking->contact_email ?: 'your email' }} after payment is confirmed.</div></td>
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
            TravelWheel Limited<br>This booking is subject to airline fare and ticketing rules.
        </td>
    </tr>
</table>
</td></tr>
</table>
</body>
</html>
