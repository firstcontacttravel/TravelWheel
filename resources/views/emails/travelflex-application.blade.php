{{-- resources/views/emails/travelflex-application.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>TravelFlex Loan Application â€” {{ $applicant['full_name'] ?? '' }}</title>
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
        <div class="header-icon">ðŸ“‹</div>
        <div class="header-title">TravelFlex Loan Application</div>
        <div class="header-sub">New loan application received â€” please review and process</div>
    </div>
    <div class="body">

        @if($bookingRef)
        <div class="ref-box">
            <div class="ref-label">Booking Reference</div>
            <div class="ref-value">{{ $bookingRef }}</div>
        </div>
        @endif

        {{-- â”€â”€ Applicant Personal Info â”€â”€ --}}
        <h4>ðŸ‘¤ Applicant Details</h4>
        <table>
            <tr><td>Full Name</td><td>{{ $applicant['full_name'] ?? 'â€”' }}</td></tr>
            <tr><td>Email Address</td><td>{{ $applicant['email'] ?? 'â€”' }}</td></tr>
            <tr><td>Home Address</td><td>{{ $applicant['home_address'] ?? 'â€”' }}</td></tr>
            <tr><td>BVN</td><td>{{ $applicant['bvn'] ?? 'â€”' }}</td></tr>
        </table>

        {{-- â”€â”€ Employment Info â”€â”€ --}}
        <h4>ðŸ’¼ Employment Details</h4>
        <table>
            <tr><td>Employer Company</td><td>{{ $applicant['employer_name'] ?? 'â€”' }}</td></tr>
            <tr><td>Employer Address</td><td>{{ $applicant['employer_address'] ?? 'â€”' }}</td></tr>
            <tr><td>Occupation</td><td>{{ $applicant['occupation'] ?? 'â€”' }}</td></tr>
            <tr><td>Job Description</td><td>{{ $applicant['job_description'] ?? 'â€”' }}</td></tr>
            <tr><td>Staff Number</td><td>{{ $applicant['staff_number'] ?? 'â€”' }}</td></tr>
        </table>

        {{-- â”€â”€ Flight Details â”€â”€ --}}
        <h4>âœˆï¸ Flight Details</h4>
        @php
            $segs     = $flightInfo['segments'] ?? [];
            $firstSeg = $segs[0] ?? [];
            $lastSeg  = !empty($segs) ? $segs[count($segs)-1] : [];
            $currency = $flightInfo['currency'] ?? 'NGN';
    $sym = match($currency) { 'NGN' => html_entity_decode('&#8358;', ENT_QUOTES, 'UTF-8'), 'USD' => '$', 'GBP' => html_entity_decode('&pound;', ENT_QUOTES, 'UTF-8'), 'EUR' => html_entity_decode('&euro;', ENT_QUOTES, 'UTF-8'), default => $currency . ' ' };
            $cabinLabel = \App\Support\FlightDisplay::cabin($flightInfo ?? []);
        @endphp
        <table>
            <tr><td>Route</td><td>{{ ($firstSeg['from']??'') }} → {{ ($lastSeg['to']??'') }}</td></tr>
            <tr><td>Travel Date</td><td>{{ $firstSeg['departDate'] ?? 'â€”' }}</td></tr>
            <tr><td>Airline</td><td>{{ $flightInfo['airline'] ?? 'â€”' }}</td></tr>
            <tr><td>Cabin</td><td>{{ $cabinLabel }}</td></tr>
            <tr><td>Fare Type</td><td><span class="badge badge-blue">{{ $flightInfo['fareType'] ?? 'â€”' }}</span></td></tr>
            <tr><td>Refundable</td><td><span class="badge {{ ($flightInfo['isRefundable']??false) ? 'badge-green' : 'badge-amber' }}">{{ ($flightInfo['isRefundable']??false) ? 'Yes' : 'No' }}</span></td></tr>
            <tr><td>Ticket Cost</td><td><strong>{{ $sym }}{{ number_format((float)($flightInfo['price']??0), 2) }}</strong></td></tr>
        </table>

        {{-- â”€â”€ Extra Services â”€â”€ --}}
        @php
            $extraServices = $flightInfo['extra_services_snapshot'] ?? [];
            $extrasTotal   = 0.0;
            if (!empty($extraServices)) {
                if (!empty($extraServices['baggage'])) {
                    foreach ($extraServices['baggage'] as $item) {
                        $extrasTotal += (float) ($item['line_total'] ?? 0);
                    }
                }
                if (!empty($extraServices['meal'])) {
                    foreach ($extraServices['meal'] as $item) {
                        $extrasTotal += (float) ($item['unit_price'] ?? 0);
                    }
                }
            }
        @endphp
        @if(!empty($extraServices['baggage']) || !empty($extraServices['meal']))
        <h4>ðŸŽ Extra Services</h4>
        <table class="schedule-table">
            <thead><tr><th>Service Type</th><th>Description</th><th>Details</th><th>Price</th></tr></thead>
            <tbody>
                @if(!empty($extraServices['baggage']))
                    @foreach($extraServices['baggage'] as $bag)
                    <tr>
                        <td>ðŸ§³ Baggage</td>
                        <td>{{ $bag['description'] ?? '' }}</td>
                        <td style="font-size:11px;">{{ ucfirst($bag['direction'] ?? '') }} · Qty: {{ $bag['quantity'] ?? 1 }}</td>
                        <td><strong>{{ $sym }}{{ number_format((float)($bag['line_total'] ?? 0), 2) }}</strong></td>
                    </tr>
                    @endforeach
                @endif
                @if(!empty($extraServices['meal']))
                    @foreach($extraServices['meal'] as $meal)
                    <tr>
                        <td>ðŸ½ï¸ Meal</td>
                        <td>{{ $meal['description'] ?? '' }}</td>
                        <td style="font-size:11px;">{{ ucfirst($meal['direction'] ?? '') }} · Segment {{ ($meal['segment'] ?? 0) + 1 }}</td>
                        <td><strong>{{ $sym }}{{ number_format((float)($meal['unit_price'] ?? 0), 2) }}</strong></td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
            <tfoot>
                <tr style="background:#f8fafc;font-weight:700;">
                    <td colspan="3">Total Extra Services</td>
                    <td><strong>{{ $sym }}{{ number_format($extrasTotal, 2) }}</strong></td>
                </tr>
            </tfoot>
        </table>
        @endif

        {{-- â”€â”€ Loan Details â”€â”€ --}}
        <h4>ðŸ’° Loan Details</h4>
        <table>
            <tr><td>Flight Cost</td><td><strong>{{ $sym }}{{ number_format((float)($flightInfo['price']??0), 2) }}</strong></td></tr>
            @if($extrasTotal > 0)<tr><td>Extra Services</td><td><strong>{{ $sym }}{{ number_format($extrasTotal, 2) }}</strong></td></tr>@endif
            @php
                $ticketCost = (float) ($loanPlan['ticket_cost'] ?? (($flightInfo['price'] ?? 0) + $extrasTotal));
                $downPayment = (float) ($loanPlan['down_payment'] ?? 0);
                $loanAmount = (float) ($loanPlan['loan_amount'] ?? $loanPlan['remaining_balance'] ?? max(0, $ticketCost - $downPayment));
            @endphp
            <tr><td>Total Loan Amount</td><td><strong style="color:#7c3aed;">{{ $sym }}{{ number_format($loanAmount, 2) }}</strong></td></tr>
            <tr><td>Down Payment</td><td>{{ $sym }}{{ number_format((float)($loanPlan['down_payment']??0), 2) }} ({{ $loanPlan['down_percent']??30 }}%)</td></tr>
            <tr><td>Total Payable</td><td>{{ $sym }}{{ number_format((float)($loanPlan['grand_total'] ?? ($ticketCost + (float)($loanPlan['total_interest'] ?? 0))), 2) }}</td></tr>
            <tr><td>Repayment Plan</td><td>{{ $loanPlan['repayment_plan'] ?? 'â€”' }}</td></tr>
            <tr><td>Total Interest</td><td>{{ $sym }}{{ number_format((float)($loanPlan['total_interest']??0), 2) }}</td></tr>
            <tr><td>Payment Method</td><td>{{ ucfirst($loanPlan['payment_method'] ?? 'â€”') }}</td></tr>
        </table>

        {{-- â”€â”€ Repayment Schedule â”€â”€ --}}
        @if(!empty($loanPlan['schedule']))
        <h4>ðŸ“… Repayment Schedule</h4>
        <table class="schedule-table">
            <thead><tr><th>#</th><th>Instalment</th><th>Due Date</th><th>Principal</th><th>Interest</th><th>Total</th></tr></thead>
            <tbody>
                @foreach($loanPlan['schedule'] as $i => $inst)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $inst['label'] ?? ($i+1).'. Payment' }}</td>
                    <td>{{ $inst['dueDate'] ?? 'â€”' }}</td>
                    <td>{{ $sym }}{{ number_format((float)($inst['principal']??0), 2) }}</td>
                    <td>{{ $sym }}{{ number_format((float)($inst['interest']??0), 2) }}</td>
                    <td><strong>{{ $sym }}{{ number_format((float)($inst['total']??0), 2) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- â”€â”€ Documents Notice â”€â”€ --}}
        <h4>ðŸ“Ž Attached Documents</h4>
        <p style="font-size:13px;color:#64748b;line-height:1.6;">
            The following documents have been attached to this email:
            Valid Government-Issued ID, Passport Photograph, Work ID Card, Employment Letter, and 6-Month Bank Statement.
            Please verify their authenticity before processing.
        </p>

        <div class="warning-box">
            âš ï¸ <strong>Action Required:</strong> Please review this application, verify all documents, and process the loan approval within 24 business hours.
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
