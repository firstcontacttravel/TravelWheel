{{-- Internal — the provider handoff package, not a customer email. Carries
     BVN, NIN and passport numbers, so it stays a dense record. --}}
@php
    $money = function ($amount, string $currency = 'NGN'): string {
        return $amount === null || $amount === ''
            ? '—'
            : $currency.' '.number_format((float) $amount, 2);
    };
    $label = fn ($value): string => filled($value)
        ? str((string) $value)->replace('_', ' ')->headline()->toString()
        : '—';

    $currency = $flightInfo['currency'] ?? 'NGN';
    $segments = $flightInfo['segments'] ?? [];
    $firstSegment = $segments[0] ?? [];
    $lastSegment = $segments ? $segments[count($segments) - 1] : [];
    $origin = $firstSegment['from'] ?? data_get($flightInfo, 'origin', '');
    $destination = $lastSegment['to'] ?? data_get($flightInfo, 'destination', '');
    $route = trim(($origin ?: '—').' to '.($destination ?: '—'));

    $ticketCost = (float) ($flightInfo['price'] ?? $loanPlan['ticket_cost'] ?? $loanPlan['base_fare'] ?? 0);
    $grandTotal = (float) ($loanPlan['grand_total'] ?? $ticketCost);
    $downPayment = (float) ($loanPlan['down_payment'] ?? 0);
    $loanAmount = (float) ($loanPlan['loan_amount'] ?? $loanPlan['remaining_balance'] ?? max(0, $ticketCost - $downPayment));
    $administrationFee = (float) ($loanPlan['administration_fee'] ?? 0);
    $insuranceFee = (float) ($loanPlan['insurance_fee'] ?? 0);
    $upfrontPaymentTotal = (float) ($loanPlan['upfront_payment_total'] ?? ($downPayment + $administrationFee + $insuranceFee));

    $applicantType = $applicant['applicant_type'] ?? 'individual';
    $documents = $applicantType === 'company' ? [
        'representative_valid_id' => 'Representative valid ID',
        'cac_status_report' => 'Status Report (Form CAC 1.1)',
        'share_certificate' => 'Share Certificate',
        'memart' => 'Memorandum and Articles of Association (MEMART)',
        'register_of_members' => 'Register of Members',
        'shareholders_agreement' => "Shareholders' Agreement",
        'return_of_allotment' => 'Return of Allotment of Shares (Form CAC 2)',
        'certificate_of_incorporation' => 'Certificate of Incorporation',
        'board_resolution' => 'Board Resolution / Authorization Letter',
        'company_bank_statement' => 'Company Bank Statement',
        'tin_certificate' => 'TIN Certificate',
    ] : [
        'valid_id' => 'Valid government ID',
        'passport_photo' => 'Passport photograph',
        'work_id_card' => 'Work ID card',
        'employment_letter' => 'Employment letter',
        'bank_statements' => 'Six-month bank statement',
    ];

    $schedule = is_array($loanPlan['schedule'] ?? null) ? $loanPlan['schedule'] : [];
@endphp

<x-mail.layout
    title="TravelFlex provider review"
    :preheader="'Review package · '.($bookingRef ?: 'booking').' · '.$money($grandTotal, $currency)"
    internal
    eyebrow="Internal · provider handoff"
    heading="Application review package"
    intro="Applicant details, documents, itinerary and repayment terms for provider approval."
    badge="Action required"
    tone="warning"
>

    <x-mail.panel
        label="Booking reference"
        :value="$bookingRef ?: '—'"
        mono
        alt-label="Planned down payment"
        :alt-value="$money($downPayment, $currency)"
        :alt-sublabel="filled($loanPlan['down_percent'] ?? null) ? $loanPlan['down_percent'].'% of ticket cost' : null"
    />

    <x-mail.heading>Applicant</x-mail.heading>
    <x-mail.rows>
        <x-mail.row label="Applicant type">{{ $label($applicantType) }}</x-mail.row>
        <x-mail.row label="Full name" strong>{{ $applicant['full_name'] ?? '—' }}</x-mail.row>
        <x-mail.row label="Email">{{ $applicant['email'] ?? '—' }}</x-mail.row>
        <x-mail.row label="Phone">{{ $applicant['phone_primary'] ?? '—' }}</x-mail.row>
        <x-mail.row label="Home address">{{ $applicant['home_address'] ?? '—' }}</x-mail.row>
        <x-mail.row label="Occupation">{{ $applicant['occupation'] ?? '—' }}</x-mail.row>
        <x-mail.row label="Sector">{{ $label($applicant['sector'] ?? null) }}</x-mail.row>
        <x-mail.row label="Employer">{{ $applicant['employer_name'] ?? '—' }}</x-mail.row>
        <x-mail.row label="Employer address">{{ $applicant['employer_address'] ?? '—' }}</x-mail.row>
        <x-mail.row label="Staff number">{{ $applicant['staff_number'] ?? '—' }}</x-mail.row>
        <x-mail.row label="Office ID">{{ $applicant['office_id'] ?? '—' }}</x-mail.row>
        <x-mail.row label="BVN">{{ $applicant['bvn'] ?? '—' }}</x-mail.row>
        <x-mail.row label="NIN">{{ $applicant['nin'] ?? '—' }}</x-mail.row>
        <x-mail.row label="Passport number">{{ $applicant['passport_number'] ?? '—' }}</x-mail.row>
    </x-mail.rows>

    <x-mail.heading>Itinerary</x-mail.heading>
    <x-mail.rows>
        <x-mail.row label="Route">{{ $route }}</x-mail.row>
        <x-mail.row label="Airline">{{ $flightInfo['airline'] ?? '—' }}</x-mail.row>
        <x-mail.row label="Ticket cost">{{ $money($ticketCost, $currency) }}</x-mail.row>
    </x-mail.rows>

    <x-mail.heading>Repayment terms</x-mail.heading>
    <x-mail.rows>
        <x-mail.row label="Plan">{{ $label($loanPlan['repayment_plan'] ?? null) }}</x-mail.row>
        <x-mail.row label="Payment method">{{ $label($loanPlan['payment_method'] ?? null) }}</x-mail.row>
        <x-mail.row label="Down payment">{{ $money($downPayment, $currency) }}</x-mail.row>
        <x-mail.row label="Administration fee">{{ $money($administrationFee, $currency) }}</x-mail.row>
        <x-mail.row label="Insurance fee">{{ $money($insuranceFee, $currency) }}</x-mail.row>
        <x-mail.row label="Upfront total">{{ $money($upfrontPaymentTotal, $currency) }}</x-mail.row>
        <x-mail.row label="Amount financed">{{ $money($loanAmount, $currency) }}</x-mail.row>
        <x-mail.row label="Total interest">{{ $money($loanPlan['total_interest'] ?? 0, $currency) }}</x-mail.row>
        <x-mail.row label="Grand total" strong>{{ $money($grandTotal, $currency) }}</x-mail.row>
    </x-mail.rows>

    @if (! empty($schedule))
        <x-mail.heading>Repayment schedule</x-mail.heading>
        <x-mail.rows>
            @foreach ($schedule as $index => $instalment)
                <x-mail.row :label="($instalment['label'] ?? 'Instalment '.($index + 1)).' · '.($instalment['due_date'] ?? $instalment['date'] ?? '—')">
                    {{ $money($instalment['amount'] ?? $instalment['total'] ?? $instalment['principal'] ?? 0, $currency) }}
                </x-mail.row>
            @endforeach
        </x-mail.rows>
    @endif

    <x-mail.heading>Attached documents</x-mail.heading>
    <x-mail.rows>
        @foreach ($documents as $key => $name)
            @php $attached = filled($uploadPaths[$key] ?? null); @endphp
            <x-mail.row :label="$name" :strong="$attached">
                {{ $attached ? 'Attached' : 'Missing' }}
            </x-mail.row>
        @endforeach
    </x-mail.rows>

    <div style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,Helvetica,sans-serif;font-size:12.5px;line-height:1.6;color:{{ config('brand.colors.muted') }};margin:-12px 0 20px;">
        Documents are attached to this email where available. Verify authenticity before completing the provider decision.
    </div>

    <x-mail.callout tone="danger" title="Confidential">
        This package contains the applicant's BVN, NIN and passport details. Use it only to assess this
        TravelFlex application.
    </x-mail.callout>

</x-mail.layout>
