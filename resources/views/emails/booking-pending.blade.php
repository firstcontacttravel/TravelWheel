{{-- resources/views/emails/booking-pending.blade.php --}}
{{-- Email-safe: tables + inline styles only --}}
@php
    $firstPax  = collect($booking->passengers_snapshot ?? [])->first();
    $firstName = $firstPax['first_name'] ?? 'Traveller';

    $currency  = $booking->currency ?? 'NGN';
    $sym       = match($currency) { 'NGN' => '₦', 'USD' => '$', 'GBP' => '£', 'EUR' => '€', default => $currency . ' ' };
    $price     = $sym . number_format((float)($booking->total_price ?? 0), 2);

    // Ticketing deadline — compute inline so blade doesn't depend on model methods
    $tktLimit    = $booking->tkt_time_limit;
    $tktFmt      = '';
    $tktHours    = 0;
    if ($tktLimit) {
        try {
            $td       = \Carbon\Carbon::parse($tktLimit);
            $tktFmt   = $td->format('D, d M Y \a\t H:i T');
            $tktHours = max(0, (int) now()->diffInHours($td, false));
        } catch (\Throwable) {}
    }

    $resumePaymentUrl = $resumePaymentUrl ?? null;
    $paymentMethod = $paymentMethod ?? ($method ?? 'bank_transfer');
    $isHoldNotice = $isHoldNotice ?? false;
    $isBankTransferNotice = $isBankTransferNotice ?? false;
    if (! $isHoldNotice && ! $isBankTransferNotice) {
        $isHoldNotice = $paymentMethod === 'hold';
        $isBankTransferNotice = $paymentMethod === 'bank_transfer';
    }
    $headline = $isHoldNotice ? 'Your Booking is On Hold' : 'Your Payment is Being Verified';
    $subhead = $isHoldNotice
        ? 'Your seat is reserved while you complete payment'
        : 'Payment received - our team is reviewing your transfer';
    $intro = $isHoldNotice
        ? "Thank you for choosing TravelWheel. Your seat has been booked on hold with the airline, and your reservation is waiting for payment. Use the secure link below to continue payment before the hold deadline."
        : "Thank you for choosing TravelWheel. We've received your payment notification and your booking is currently on hold with the airline while our team verifies your transfer.";
    $statusRows = $isHoldNotice
        ? [
            ['done', '1', 'Seat Held', 'Your booking has been placed on hold with the airline.'],
            ['current', '2', 'Payment Pending', 'Complete payment online or by bank transfer before the deadline below.'],
            ['pending', '3', 'E-Ticket Issued', 'Your ticket will be sent to ' . $booking->contact_email . ' once payment is confirmed.'],
        ]
        : [
            ['done', '1', 'Booking Created', 'Your seats are reserved. Ref: ' . $booking->booking_ref],
            ['done', '2', 'Payment Notified', "You've confirmed that payment was made."],
            ['current', '3', 'Verification in Progress', 'Our team is checking your payment. Expected turnaround: 2-4 business hours.'],
            ['pending', '4', 'E-Ticket Issued', 'Your ticket will be emailed to ' . $booking->contact_email . ' immediately after verification.'],
        ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking On Hold – {{ $booking->booking_ref }}</title>
<style>
    body{margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Arial,sans-serif;font-size:14px;color:#0f172a}
    .wrap{max-width:600px;margin:32px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.1)}
    .header{background:linear-gradient(135deg,#0a1940,#1e3a8a);padding:28px 32px;text-align:center;color:#fff}
    .header-icon{font-size:42px;margin-bottom:8px}
    .header-title{font-size:20px;font-weight:800;margin-bottom:4px}
    .header-sub{font-size:13px;opacity:.85}
    .body{padding:28px 32px}
    .ref-box{background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:14px 18px;margin-bottom:22px;text-align:center}
    .ref-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#92400e;margin-bottom:4px}
    .ref-value{font-size:22px;font-weight:800;color:#78350f;font-family:'Courier New',monospace;letter-spacing:.05em}
    .section-title{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#94a3b8;margin-bottom:12px;margin-top:20px}
    table.detail{width:100%;border-collapse:collapse;font-size:13px;margin-bottom:8px}
    table.detail td{padding:8px 0;border-bottom:1px solid #f1f5f9;vertical-align:top}
    table.detail td:first-child{color:#64748b;width:40%}
    table.detail td:last-child{font-weight:700;text-align:right}
    .deadline-box{background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:14px 18px;margin:18px 0}
    .deadline-title{font-size:13px;font-weight:800;color:#92400e;margin-bottom:4px}
    .deadline-sub{font-size:12px;color:#78350f;line-height:1.6}
    .btn-wrap{text-align:center;margin:24px 0 8px}
    .btn{display:inline-block;padding:13px 22px;border-radius:10px;background:#0f172a;color:#ffffff!important;text-decoration:none;font-size:13px;font-weight:800}
    .disclaimer{background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:14px 18px;margin:18px 0;font-size:12px;color:#78350f;line-height:1.7}
    .footer{background:#f8fafc;padding:20px 32px;text-align:center;border-top:1px solid #f1f5f9;font-size:12px;color:#94a3b8}
    .footer a{color:#1d4ed8;text-decoration:none}
</style>
</head>
<body>
<div class="wrap">

    <div class="header">
        <div class="header-icon">📬</div>
        <div class="header-title">{{ $headline }}</div>
        <div class="header-sub">{{ $subhead }}</div>
    </div>

    <div class="body">
        <p style="font-size:14px;line-height:1.7;margin-bottom:20px;color:#334155">
            Hi <strong>{{ $firstName }}</strong>,<br><br>
            {{ $intro }}
        </p>

        <div class="ref-box">
            <div class="ref-label">Booking Reference</div>
            <div class="ref-value">{{ $booking->booking_ref }}</div>
        </div>

        <div class="section-title">Booking Details</div>
        <table class="detail">
            <tr><td>Route</td><td>{{ $booking->route }}</td></tr>
            <tr><td>Airline</td><td>{{ $booking->airline }}</td></tr>
            <tr><td>Cabin</td><td>{{ $booking->cabin }}</td></tr>
            <tr><td>Fare Type</td><td>{{ $booking->fare_type }}</td></tr>
            <tr><td>Total Amount</td><td style="color:#0a1940">{{ $price }}</td></tr>
            {{--<tr><td>Hold Reference</td><td>{{ $booking->unique_id ?: 'N/A' }}</td></tr>--}}
            <tr><td>Payment Method</td><td>{{ $isBankTransferNotice ? 'Bank Transfer' : 'Online or Bank Transfer' }}</td></tr>
        </table>

        @if($tktFmt)
        <div class="deadline-box">
            <div class="deadline-title">Ticketing Deadline</div>
            <div class="deadline-sub">
                Your booking hold expires on <strong>{{ $tktFmt }}</strong>
                ({{ $tktHours }} hour{{ $tktHours === 1 ? '' : 's' }} remaining).
                Please ensure payment is completed before this time.
            </div>
        </div>
        @endif

        @if($resumePaymentUrl)
        <div class="btn-wrap">
            <a href="{{ $resumePaymentUrl }}" class="btn">Continue Payment</a>
        </div>
        <p style="font-size:12px;color:#64748b;line-height:1.7;text-align:center;margin-bottom:0">
            You can use this link later to return directly to your payment options.
        </p>
        @endif

        <div class="disclaimer">
            Your reservation is held subject to airline rules and availability. It can be canceled at the discretion of the airline on or before the due date.
        </div>

        <div class="section-title">What Happens Next</div>

        {{-- Steps rendered as a table for email-client compatibility --}}
        <table width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0">
            @foreach($statusRows as [$state, $icon, $title, $sub])
            <tr style="margin-bottom:14px">
                <td style="width:36px;vertical-align:top;padding:0 12px 14px 0">
                    <div style="
                        width:28px;height:28px;border-radius:50%;
                        background:{{ $state === 'done' ? '#059669' : ($state === 'current' ? '#d97706' : '#94a3b8') }};
                        color:#fff;font-size:12px;font-weight:800;
                        text-align:center;line-height:28px;
                    ">{{ $icon }}</div>
                </td>
                <td style="padding-bottom:14px;vertical-align:top">
                    <div style="font-size:13px;font-weight:700;color:#0f172a;margin-bottom:2px">{{ $title }}</div>
                    <div style="font-size:12px;color:#64748b;line-height:1.5">{{ $sub }}</div>
                </td>
            </tr>
            @endforeach
        </table>

        <p style="font-size:13px;color:#64748b;line-height:1.7;margin-top:16px">
            <strong>Need help?</strong> Our team is available Mon–Fri 8am–6pm WAT.<br>
            Email: <a href="mailto:support@travelwheel.ng">support@travelwheel.ng</a>
            | Phone: +234 800 000 0000<br>
            Always quote your booking reference: <strong>{{ $booking->booking_ref }}</strong>
        </p>
    </div>

    <div class="footer">
        &copy; {{ date('Y') }} TravelWheel. All rights reserved.<br>
        <a href="#">Privacy Policy</a> &middot; <a href="#">Terms of Service</a>
    </div>

</div>
</body>
</html>
