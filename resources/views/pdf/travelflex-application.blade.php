<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 26px 30px; }
        body { font-family: DejaVu Sans, sans-serif; color: #101828; font-size: 11px; line-height: 1.45; }
        .header { border-bottom: 3px solid #39328f; padding-bottom: 12px; margin-bottom: 16px; }
        .kicker { color: #39328f; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; }
        .title { font-size: 22px; font-weight: 800; margin-top: 4px; }
        .meta { color: #667085; margin-top: 4px; }
        .grid { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .grid td { vertical-align: top; width: 50%; padding: 0 8px 8px 0; }
        .section { margin-top: 12px; page-break-inside: avoid; }
        .section-title { background: #f5f7ff; color: #39328f; padding: 7px 9px; font-weight: 800; border: 1px solid #e6e9f0; }
        .rows { border-left: 1px solid #e6e9f0; border-right: 1px solid #e6e9f0; border-bottom: 1px solid #e6e9f0; }
        .row { padding: 6px 9px; border-bottom: 1px solid #eef1f6; }
        .row:last-child { border-bottom: 0; }
        .label { color: #667085; font-size: 9px; text-transform: uppercase; letter-spacing: .05em; }
        .value { margin-top: 2px; font-weight: 600; overflow-wrap: break-word; }
        .summary { background: #101828; color: #fff; padding: 12px; margin-bottom: 14px; }
        .summary table { width: 100%; border-collapse: collapse; }
        .summary td { width: 25%; padding-right: 10px; vertical-align: top; }
        .summary .label { color: #cfd4df; }
        .summary .value { color: #fff; }
        .signature { max-width: 220px; max-height: 82px; border: 1px solid #e6e9f0; padding: 8px; background: #fff; }
        .footer { margin-top: 18px; color: #667085; font-size: 9px; border-top: 1px solid #e6e9f0; padding-top: 8px; }
    </style>
</head>
<body>
@php
    $fmt = fn ($value) => is_numeric($value) ? 'NGN ' . number_format((float) $value, 2) : ($value ?: '-');
    $showRows = function (array $rows) {
        foreach ($rows as $label => $value) {
            echo '<div class="row"><div class="label">' . e($label) . '</div><div class="value">' . e(filled($value) ? $value : '-') . '</div></div>';
        }
    };
@endphp

<div class="header">
    <div class="kicker">TravelFlex Provider Review Packet</div>
    <div class="title">Fast Credit Loan Application</div>
    <div class="meta">Generated {{ $generatedAt->format('M d, Y H:i') }} WAT | Reference {{ $application->booking_ref ?: $application->unique_id ?: '-' }}</div>
</div>

<div class="summary">
    <table>
        <tr>
            <td><div class="label">Route</div><div class="value">{{ $route ?: '-' }}</div></td>
            <td><div class="label">Ticket cost</div><div class="value">{{ $fmt($plan['ticket_cost'] ?? null) }}</div></td>
            <td><div class="label">Down payment</div><div class="value">{{ $fmt($plan['down_payment'] ?? null) }}</div></td>
            <td><div class="label">Due after approval</div><div class="value">{{ $fmt($plan['upfront_payment_total'] ?? (($plan['down_payment'] ?? 0) + ($plan['administration_fee'] ?? 0) + ($plan['insurance_fee'] ?? 0))) }}</div></td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">Applicant Details</div>
    <div class="rows">
        @php($showRows([
            'Applicant type' => ucfirst($application->applicant_type ?: data_get($applicant, 'applicant_type', 'individual')),
            'Full name' => data_get($applicant, 'full_name'),
            'Email' => data_get($applicant, 'email'),
            'Phone primary' => data_get($applicant, 'phone_primary'),
            'Phone secondary' => data_get($applicant, 'phone_secondary'),
            'Residential address' => data_get($applicant, 'home_address'),
            'BVN last four' => data_get($application->bvn_metadata, 'last_four'),
        ]))
    </div>
</div>

@if($application->applicant_type === 'company')
<div class="section">
    <div class="section-title">Company Details</div>
    <div class="rows">
        @php($showRows([
            'Company name' => data_get($company, 'company_name'),
            'RC number' => data_get($company, 'rc_number'),
            'Email' => data_get($company, 'email'),
            'Phone' => data_get($company, 'phone'),
            'Registered address' => data_get($company, 'registered_address'),
            'Sector' => data_get($company, 'sector'),
            'Bank name' => data_get($company, 'bank_name'),
            'Account number' => data_get($company, 'account_number'),
            'Loan purpose' => data_get($company, 'loan_purpose'),
        ]))
    </div>
</div>
@else
<div class="section">
    <div class="section-title">Identity Details</div>
    <div class="rows">
        @php($showRows([
            'NIN' => data_get($identity, 'nin'),
            'Title' => data_get($identity, 'title'),
            'Surname' => data_get($identity, 'surname'),
            'First name' => data_get($identity, 'first_name'),
            'Other name' => data_get($identity, 'other_name'),
            'Marital status' => data_get($identity, 'marital_status'),
            'Gender' => data_get($identity, 'gender'),
            'Date of birth' => data_get($identity, 'date_of_birth'),
            'Passport number' => data_get($identity, 'passport_number'),
            'Passport expiry' => data_get($identity, 'passport_expiry_date'),
        ]))
    </div>
</div>

<div class="section">
    <div class="section-title">Employment And Bank Details</div>
    <div class="rows">
        @php($showRows([
            'Employer' => data_get($employment, 'employer_name'),
            'Employer address' => data_get($employment, 'employer_address'),
            'Occupation' => data_get($employment, 'occupation'),
            'Job description' => data_get($employment, 'job_description'),
            'Staff number' => data_get($employment, 'staff_number'),
            'Sector' => data_get($employment, 'sector'),
            'IPPIS number' => data_get($employment, 'ippis_number'),
            'Monthly salary' => $fmt(data_get($bank, 'monthly_salary')),
            'Salary account number' => data_get($bank, 'salary_account_number'),
            'Bank name' => data_get($bank, 'bank_name'),
        ]))
    </div>
</div>

<div class="section">
    <div class="section-title">Next Of Kin</div>
    <div class="rows">
        @php($showRows([
            'Surname' => data_get($nextOfKin, 'surname'),
            'First name' => data_get($nextOfKin, 'first_name'),
            'Relationship' => data_get($nextOfKin, 'relationship'),
            'Address' => data_get($nextOfKin, 'residential_address'),
            'Phone primary' => data_get($nextOfKin, 'phone_primary'),
            'Email' => data_get($nextOfKin, 'email'),
        ]))
    </div>
</div>
@endif

<div class="section">
    <div class="section-title">Repayment Plan</div>
    <div class="rows">
        @php($showRows([
            'Loan amount' => $fmt($plan['loan_amount'] ?? $plan['remaining_balance'] ?? null),
            'Administration fee' => $fmt($plan['administration_fee'] ?? null),
            'Insurance fee' => $fmt($plan['insurance_fee'] ?? null),
            'Due after approval' => $fmt($plan['upfront_payment_total'] ?? null),
            'Grand total' => $fmt($plan['grand_total'] ?? null),
            'Total interest' => $fmt($plan['total_interest'] ?? null),
            'Interest rate' => isset($plan['interest_rate_percent']) ? $plan['interest_rate_percent'] . '%' : null,
        ]))
        @foreach(($plan['schedule'] ?? []) as $instalment)
            <div class="row">
                <div class="label">{{ $instalment['label'] ?? 'Instalment' }}</div>
                <div class="value">Due {{ $instalment['due_date'] ?? $instalment['dueDate'] ?? '-' }} - Principal {{ $fmt($instalment['principal'] ?? null) }} - Interest {{ $fmt($instalment['interest'] ?? null) }} - Total {{ $fmt($instalment['total'] ?? null) }}</div>
            </div>
        @endforeach
    </div>
</div>

<div class="section">
    <div class="section-title">Agreement And Signature</div>
    <div class="rows">
        @php($showRows([
            'Agreement version' => data_get($agreement, 'version'),
            'Accepted at' => data_get($agreement, 'accepted_at'),
            'Typed signature' => data_get($agreement, 'digital_signature'),
            'IP address' => data_get($agreement, 'ip_address'),
        ]))
        @if(filled(data_get($agreement, 'signature_image')))
            <div class="row">
                <div class="label">Drawn signature</div>
                <div class="value"><img class="signature" src="{{ data_get($agreement, 'signature_image') }}" alt="Signature"></div>
            </div>
        @endif
    </div>
</div>

<div class="footer">
    This packet was generated from the TravelWheel TravelFlex application submitted online. Supporting uploaded documents are attached separately in the provider email.
</div>
</body>
</html>
