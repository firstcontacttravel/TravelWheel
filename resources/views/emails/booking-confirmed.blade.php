{{-- resources/views/emails/booking-confirmed.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking Confirmed – {{ $booking->unique_id }}</title>
<style>
    body{margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',sans-serif;font-size:14px;color:#0f172a}
    .wrap{max-width:600px;margin:32px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.1)}
    .header{background:linear-gradient(135deg,#064e3b,#059669);padding:28px 32px;text-align:center;color:#fff}
    .header-icon{font-size:48px;margin-bottom:8px}
    .header-title{font-size:22px;font-weight:800;margin-bottom:4px}
    .header-sub{font-size:13px;opacity:.85}
    .body{padding:28px 32px}
    .ref-box{background:#f0fdf4;border:1px solid #a7f3d0;border-radius:10px;padding:16px 20px;margin-bottom:22px;text-align:center}
    .ref-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#065f46;margin-bottom:4px}
    .ref-value{font-size:24px;font-weight:800;color:#064e3b;font-family:'Courier New',monospace;letter-spacing:.06em}
    .ref-sub{font-size:12px;color:#059669;margin-top:4px;font-weight:600}
    .section-title{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#94a3b8;margin-bottom:12px;margin-top:22px}
    table.detail{width:100%;border-collapse:collapse;font-size:13px}
    table.detail td{padding:8px 0;border-bottom:1px solid #f1f5f9;vertical-align:top}
    table.detail td:first-child{color:#64748b;width:40%}
    table.detail td:last-child{font-weight:700;text-align:right}
    .pax-table{width:100%;border-collapse:collapse;font-size:12.5px;margin-top:4px;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0}
    .pax-table th{background:#f8fafc;padding:8px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#94a3b8;border-bottom:1px solid #e2e8f0}
    .pax-table td{padding:10px 12px;border-bottom:1px solid #f1f5f9;color:#334155}
    .pax-table tr:last-child td{border-bottom:none}
    .total-row{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;background:#f0fdf4;border-radius:10px;margin-top:18px}
    .total-lbl{font-size:14px;font-weight:800;color:#064e3b}
    .total-val{font-size:22px;font-weight:800;color:#064e3b;font-family:'Courier New',monospace}
    .footer{background:#f8fafc;padding:20px 32px;text-align:center;border-top:1px solid #f1f5f9;font-size:12px;color:#94a3b8}
    .footer a{color:#1d4ed8;text-decoration:none}
</style>
</head>
<body>
<div class="wrap">
    <div class="header">
        <div class="header-icon">✅</div>
        <div class="header-title">Booking Confirmed!</div>
        <div class="header-sub">Your e-ticket has been issued. Have a great flight!</div>
    </div>
    <div class="body">
        <p style="font-size:14px;line-height:1.7;margin-bottom:20px;color:#334155">
            Hi {{ collect($booking->passengers_snapshot ?? [])->first()['first_name'] ?? 'Traveller' }},<br><br>
            Great news! Your booking is <strong>confirmed</strong> and your e-ticket has been issued. 
            Please save or print this email and present it at the airport check-in counter.
        </p>

        <div class="ref-box">
            <div class="ref-label">Booking Reference</div>
            <div class="ref-value">{{ $booking->unique_id }}</div>
            <div class="ref-sub">✓ Ticket Issued</div>
        </div>

        <div class="section-title">Flight Details</div>
        <table class="detail">
            <tr><td>Route</td><td>{{ $booking->route }}</td></tr>
            <tr><td>Airline</td><td>{{ $booking->airline }}</td></tr>
            <tr><td>Cabin</td><td>{{ $booking->cabin }}</td></tr>
            <tr><td>Fare Type</td><td>{{ $booking->fare_type }}</td></tr>
            <tr><td>Payment Method</td><td>{{ ucfirst(str_replace('_', ' ', $booking->payment_method ?? '')) }}</td></tr>
        </table>

        @if(!empty($booking->passengers_snapshot))
        <div class="section-title">Passengers</div>
        <table class="pax-table">
            <thead><tr><th>#</th><th>Name</th><th>Type</th><th>DOB</th><th>Passport</th></tr></thead>
            <tbody>
                @foreach($booking->passengers_snapshot as $i => $pax)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td><strong>{{ $pax['title']??'' }} {{ strtoupper($pax['first_name']??'') }} {{ strtoupper($pax['last_name']??'') }}</strong></td>
                    <td>{{ match($pax['type']??'ADT'){'ADT'=>'Adult','CHD'=>'Child','INF'=>'Infant',default=>'Pax'} }}</td>
                    <td>{{ !empty($pax['dob']) ? \Carbon\Carbon::parse($pax['dob'])->format('d M Y') : '—' }}</td>
                    <td>{{ $pax['passport_no'] ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <div class="total-row">
            <span class="total-lbl">Total Paid</span>
            <span class="total-val">{{ $booking->formattedPrice() }}</span>
        </div>

        <p style="font-size:13px;color:#64748b;line-height:1.6;margin-top:22px">
            <strong>Important reminders:</strong><br>
            • Arrive at the airport at least 2 hours before domestic / 3 hours before international flights.<br>
            • Carry a valid photo ID or passport matching the name on this ticket.<br>
            • Check airline baggage allowance on the booking.<br><br>
            <strong>Need help?</strong> Contact us at <a href="mailto:support@travelwheel.com">support@travelwheel.com</a> 
            or call +234 800 000 0000. Quote ref: <strong>{{ $booking->unique_id }}</strong>.
        </p>
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} TravelWheel. All rights reserved.<br>
        <a href="#">Privacy Policy</a> &middot; <a href="#">Terms of Service</a>
    </div>
</div>
</body>
</html>