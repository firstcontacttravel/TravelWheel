{{-- resources/views/emails/travelflex-application.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>TravelFlex Loan Application — {{ $applicant['full_name'] ?? '' }}</title>
<style>
    body{margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',sans-serif;font-size:14px;color:#0f172a}
    .wrap{max-width:650px;margin:28px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.09)}
    .header{background:linear-gradient(135deg,#0a1940,#4338ca,#7c3aed);padding:28px 32px;text-align:center;color:#fff}
    .header-icon{font-size:40px;margin-bottom:8px}
    .header-title{font-size:20px;font-weight:800;margin-bottom:4px}
    .header-sub{font-size:13px;opacity:.82}
    .body{padding:28px 32px}
    h4{font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#94a3b8;margin:20px 0 10px;padding-bottom:6px;border-bottom:1px solid #f1f5f9}
    h4:first-child{margin-top:0}
    table{width:100%;border-collapse:collapse;font-size:13px;margin-bottom:4px}
    td{padding:8px 0;border-bottom:1px solid #f8fafc;vertical-align:top}
    td:first-child{color:#64748b;width:42%;padding-right:12px}
    td:last-child{font-weight:700;color:#0f172a}
    .schedule-table{width:100%;border-collapse:collapse;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0}
    .schedule-table th{background:#f8fafc;padding:8px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#94a3b8;border-bottom:1px solid #e2e8f0}
    .schedule-table td{padding:9px 12px;border-bottom:1px solid #f1f5f9;font-size:12.5px}
    .badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700}
    .badge-blue{background:#eff6ff;color:#1d4ed8}
    .badge-green{background:#f0fdf4;color:#059669}
    .badge-amber{background:#fff7ed;color:#d97706}
    .ref-box{background:#f0fdf4;border:1px solid #a7f3d0;border-radius:10px;padding:14px 18px;text-align:center;margin-bottom:20px}
    .ref-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#065f46;margin-bottom:4px}
    .ref-value{font-size:20px;font-weight:800;color:#064e3b;font-family:'Courier New',monospace;letter-spacing:.05em}
    .warning-box{background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:14px 18px;margin-top:18px;font-size:13px;color:#92400e}
    .footer{background:#f8fafc;padding:18px 32px;text-align:center;font-size:12px;color:#94a3b8;border-top:1px solid #f1f5f9}
</style>
</head>
<body>
<div class="wrap">
    <div class="header">
        <div class="header-icon">📋</div>
        <div class="header-title">TravelFlex Loan Application</div>
        <div class="header-sub">New loan application received — please review and process</div>
    </div>
    <div class="body">

        @if($bookingRef)
        <div class="ref-box">
            <div class="ref-label">Booking Reference</div>
            <div class="ref-value">{{ $bookingRef }}</div>
        </div>
        @endif

        {{-- ── Applicant Personal Info ── --}}
        <h4>👤 Applicant Details</h4>
        <table>
            <tr><td>Full Name</td><td>{{ $applicant['full_name'] ?? '—' }}</td></tr>
            <tr><td>Email Address</td><td>{{ $applicant['email'] ?? '—' }}</td></tr>
            <tr><td>Home Address</td><td>{{ $applicant['home_address'] ?? '—' }}</td></tr>
            <tr><td>BVN</td><td>{{ $applicant['bvn'] ?? '—' }}</td></tr>
        </table>

        {{-- ── Employment Info ── --}}
        <h4>💼 Employment Details</h4>
        <table>
            <tr><td>Employer Company</td><td>{{ $applicant['employer_name'] ?? '—' }}</td></tr>
            <tr><td>Employer Address</td><td>{{ $applicant['employer_address'] ?? '—' }}</td></tr>
            <tr><td>Occupation</td><td>{{ $applicant['occupation'] ?? '—' }}</td></tr>
            <tr><td>Job Description</td><td>{{ $applicant['job_description'] ?? '—' }}</td></tr>
            <tr><td>Staff Number</td><td>{{ $applicant['staff_number'] ?? '—' }}</td></tr>
        </table>

        {{-- ── Flight Details ── --}}
        <h4>✈️ Flight Details</h4>
        @php
            $segs     = $flightInfo['segments'] ?? [];
            $firstSeg = $segs[0] ?? [];
            $lastSeg  = !empty($segs) ? $segs[count($segs)-1] : [];
            $currency = $flightInfo['currency'] ?? 'NGN';
            $sym      = match($currency) { 'NGN' => '₦', 'USD' => '$', 'GBP' => '£', 'EUR' => '€', default => $currency.' ' };
        @endphp
        <table>
            <tr><td>Route</td><td>{{ ($firstSeg['from']??'') }} → {{ ($lastSeg['to']??'') }}</td></tr>
            <tr><td>Travel Date</td><td>{{ $firstSeg['departDate'] ?? '—' }}</td></tr>
            <tr><td>Airline</td><td>{{ $flightInfo['airline'] ?? '—' }}</td></tr>
            <tr><td>Cabin</td><td>{{ $flightInfo['cabin'] ?? 'Economy' }}</td></tr>
            <tr><td>Fare Type</td><td><span class="badge badge-blue">{{ $flightInfo['fareType'] ?? '—' }}</span></td></tr>
            <tr><td>Refundable</td><td><span class="badge {{ ($flightInfo['isRefundable']??false) ? 'badge-green' : 'badge-amber' }}">{{ ($flightInfo['isRefundable']??false) ? 'Yes' : 'No' }}</span></td></tr>
            <tr><td>Ticket Cost</td><td><strong>{{ $sym }}{{ number_format((float)($flightInfo['price']??0), 2) }}</strong></td></tr>
        </table>

        {{-- ── Loan Details ── --}}
        <h4>💰 Loan Details</h4>
        <table>
            <tr><td>Total Loan Amount</td><td><strong>{{ $sym }}{{ number_format((float)($loanPlan['grand_total']??0), 2) }}</strong></td></tr>
            <tr><td>Down Payment</td><td>{{ $sym }}{{ number_format((float)($loanPlan['down_payment']??0), 2) }} ({{ $loanPlan['down_percent']??30 }}%)</td></tr>
            <tr><td>Remaining Balance</td><td>{{ $sym }}{{ number_format((float)($flightInfo['price']??0) - (float)($loanPlan['down_payment']??0), 2) }}</td></tr>
            <tr><td>Repayment Plan</td><td>{{ $loanPlan['repayment_plan'] ?? '—' }}</td></tr>
            <tr><td>Total Interest</td><td>{{ $sym }}{{ number_format((float)($loanPlan['total_interest']??0), 2) }}</td></tr>
            <tr><td>Payment Method</td><td>{{ ucfirst($loanPlan['payment_method'] ?? '—') }}</td></tr>
        </table>

        {{-- ── Repayment Schedule ── --}}
        @if(!empty($loanPlan['schedule']))
        <h4>📅 Repayment Schedule</h4>
        <table class="schedule-table">
            <thead><tr><th>#</th><th>Instalment</th><th>Due Date</th><th>Principal</th><th>Interest</th><th>Total</th></tr></thead>
            <tbody>
                @foreach($loanPlan['schedule'] as $i => $inst)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $inst['label'] ?? ($i+1).'. Payment' }}</td>
                    <td>{{ $inst['dueDate'] ?? '—' }}</td>
                    <td>{{ $sym }}{{ number_format((float)($inst['principal']??0), 2) }}</td>
                    <td>{{ $sym }}{{ number_format((float)($inst['interest']??0), 2) }}</td>
                    <td><strong>{{ $sym }}{{ number_format((float)($inst['total']??0), 2) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- ── Documents Notice ── --}}
        <h4>📎 Attached Documents</h4>
        <p style="font-size:13px;color:#64748b;line-height:1.6;">
            The following documents have been attached to this email:
            Valid Government-Issued ID, Passport Photograph, Work ID Card, Employment Letter, and 6-Month Bank Statement.
            Please verify their authenticity before processing.
        </p>

        <div class="warning-box">
            ⚠️ <strong>Action Required:</strong> Please review this application, verify all documents, and process the loan approval within 24 business hours. 
            Contact the applicant at <strong>{{ $applicant['email'] ?? '' }}</strong> with your decision.
        </div>
    </div>
    <div class="footer">
        This is an automated message from the Travelwheel TravelFlex system.<br>
        Travelwheel Limited · support@travelwheel.com · +234 800 000 0000<br>
        &copy; {{ date('Y') }} Travelwheel Limited. All rights reserved.
    </div>
</div>
</body>
</html>