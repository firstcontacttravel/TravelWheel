<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TravelFlex Provider Review</title>
</head>
<body style="margin:0;padding:0;background:#f5f7fb;color:#111827;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;">
@php
    $money = function ($amount, string $currency = 'NGN'): string {
        if ($amount === null || $amount === '') {
            return '-';
        }

        return $currency . ' ' . number_format((float) $amount, 2);
    };

    $label = fn ($value): string => filled($value) ? str((string) $value)->replace('_', ' ')->headline()->toString() : '-';
    $currency = $flightInfo['currency'] ?? 'NGN';
    $segments = $flightInfo['segments'] ?? [];
    $firstSegment = $segments[0] ?? [];
    $lastSegment = $segments ? $segments[count($segments) - 1] : [];
    $origin = $firstSegment['from'] ?? data_get($flightInfo, 'origin', '');
    $destination = $lastSegment['to'] ?? data_get($flightInfo, 'destination', '');
    $route = trim(($origin ?: '-') . ' to ' . ($destination ?: '-'));
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
    $panel = 'background:#ffffff;border:1px solid #e6eaf2;border-radius:16px;';
    $cellLabel = 'padding:10px 0;color:#6b7280;font-size:13px;line-height:18px;border-bottom:1px solid #eef2f7;';
    $cellValue = 'padding:10px 0;color:#111827;font-size:13px;font-weight:700;line-height:18px;border-bottom:1px solid #eef2f7;text-align:right;';
@endphp

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f5f7fb;">
    <tr>
        <td align="center" style="padding:28px 14px;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:760px;">
                <tr>
                    <td style="padding:0 0 14px;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="{{ $panel }} overflow:hidden;">
                            <tr>
                                <td style="padding:26px 28px;background:#2f2c90;color:#ffffff;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <td style="vertical-align:top;">
                                                <div style="font-size:12px;letter-spacing:.12em;text-transform:uppercase;font-weight:700;color:#dbeafe;">TravelFlex provider review</div>
                                                <div style="margin-top:8px;font-size:26px;line-height:32px;font-weight:800;">Application review package</div>
                                                <div style="margin-top:8px;font-size:14px;line-height:20px;color:#e5e7eb;">Review applicant details, documents, itinerary, and repayment terms for provider approval.</div>
                                            </td>
                                            <td align="right" style="vertical-align:top;width:190px;">
                                                <div style="display:inline-block;background:#ffffff;color:#2f2c90;border-radius:999px;padding:7px 12px;font-size:12px;font-weight:800;">Provider handoff</div>
                                                <div style="margin-top:14px;font-size:12px;color:#dbeafe;">Booking reference</div>
                                                <div style="font-size:18px;font-weight:800;letter-spacing:.04em;">{{ $bookingRef ?: '-' }}</div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:20px 28px;background:#ffffff;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <td style="padding:0 8px 8px 0;width:33.33%;">
                                                <div style="background:#f8fafc;border:1px solid #e6eaf2;border-radius:12px;padding:14px;">
                                                    <div style="font-size:11px;color:#6b7280;text-transform:uppercase;font-weight:700;">{{ $applicantType === 'company' ? 'Representative' : 'Applicant' }}</div>
                                                    <div style="margin-top:6px;font-size:15px;font-weight:800;color:#111827;">{{ $applicant['full_name'] ?? '-' }}</div>
                                                    <div style="margin-top:4px;font-size:12px;color:#6b7280;">{{ $applicant['email'] ?? '-' }}</div>
                                                </div>
                                            </td>
                                            <td style="padding:0 4px 8px;width:33.33%;">
                                                <div style="background:#f8fafc;border:1px solid #e6eaf2;border-radius:12px;padding:14px;">
                                                    <div style="font-size:11px;color:#6b7280;text-transform:uppercase;font-weight:700;">Planned down payment</div>
                                                    <div style="margin-top:6px;font-size:15px;font-weight:800;color:#111827;">{{ $money($downPayment, $currency) }}</div>
                                                    <div style="margin-top:4px;font-size:12px;color:#6b7280;">{{ $loanPlan['down_percent'] ?? '-' }}% due after approval</div>
                                                </div>
                                            </td>
                                            <td style="padding:0 0 8px 8px;width:33.33%;">
                                                <div style="background:#f8fafc;border:1px solid #e6eaf2;border-radius:12px;padding:14px;">
                                                    <div style="font-size:11px;color:#6b7280;text-transform:uppercase;font-weight:700;">Route</div>
                                                    <div style="margin-top:6px;font-size:15px;font-weight:800;color:#111827;">{{ $route }}</div>
                                                    <div style="margin-top:4px;font-size:12px;color:#6b7280;">{{ $firstSegment['departDate'] ?? '-' }}</div>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:0 0 14px;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                            <tr>
                                <td valign="top" style="width:50%;padding-right:7px;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="{{ $panel }}">
                                        <tr><td style="padding:20px 22px 4px;font-size:16px;font-weight:800;">Applicant details</td></tr>
                                        <tr>
                                            <td style="padding:0 22px 18px;">
                                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                                    <tr><td style="{{ $cellLabel }}">Full name</td><td style="{{ $cellValue }}">{{ $applicant['full_name'] ?? '-' }}</td></tr>
                                                    <tr><td style="{{ $cellLabel }}">Applicant type</td><td style="{{ $cellValue }}">{{ $label($applicantType) }}</td></tr>
                                                    <tr><td style="{{ $cellLabel }}">Email</td><td style="{{ $cellValue }}">{{ $applicant['email'] ?? '-' }}</td></tr>
                                                    <tr><td style="{{ $cellLabel }}">Phone</td><td style="{{ $cellValue }}">{{ $applicant['phone_primary'] ?? '-' }}</td></tr>
                                                    <tr><td style="{{ $cellLabel }}">Home address</td><td style="{{ $cellValue }}">{{ $applicant['home_address'] ?? '-' }}</td></tr>
                                                    <tr><td style="{{ $cellLabel }}">BVN</td><td style="{{ $cellValue }}">{{ $applicant['bvn'] ?? '-' }}</td></tr>
                                                    <tr><td style="{{ $cellLabel }}">NIN</td><td style="{{ $cellValue }}">{{ $applicant['nin'] ?? '-' }}</td></tr>
                                                    <tr><td style="{{ $cellLabel }}">Passport</td><td style="{{ $cellValue }}">{{ $applicant['passport_number'] ?? '-' }}</td></tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td valign="top" style="width:50%;padding-left:7px;">
                                    @if($applicantType === 'company')
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="{{ $panel }}">
                                        <tr><td style="padding:20px 22px 4px;font-size:16px;font-weight:800;">Company details</td></tr>
                                        <tr>
                                            <td style="padding:0 22px 18px;">
                                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                                    <tr><td style="{{ $cellLabel }}">Company</td><td style="{{ $cellValue }}">{{ data_get($applicant, 'company_details.company_name', '-') }}</td></tr>
                                                    <tr><td style="{{ $cellLabel }}">Company email</td><td style="{{ $cellValue }}">{{ data_get($applicant, 'company_details.email', '-') }}</td></tr>
                                                    <tr><td style="{{ $cellLabel }}">Company phone</td><td style="{{ $cellValue }}">{{ data_get($applicant, 'company_details.phone', '-') }}</td></tr>
                                                    <tr><td style="{{ $cellLabel }}">Sector</td><td style="{{ $cellValue }}">{{ data_get($applicant, 'company_details.sector', '-') }}</td></tr>
                                                    <tr><td style="{{ $cellLabel }}">Representative role</td><td style="{{ $cellValue }}">{{ data_get($applicant, 'representative_details.role', '-') }}</td></tr>
                                                    <tr><td style="{{ $cellLabel }}">Office ID</td><td style="{{ $cellValue }}">{{ $applicant['office_id'] ?? '-' }}</td></tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                    @else
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="{{ $panel }}">
                                        <tr><td style="padding:20px 22px 4px;font-size:16px;font-weight:800;">Employment details</td></tr>
                                        <tr>
                                            <td style="padding:0 22px 18px;">
                                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                                    <tr><td style="{{ $cellLabel }}">Employer</td><td style="{{ $cellValue }}">{{ $applicant['employer_name'] ?? '-' }}</td></tr>
                                                    <tr><td style="{{ $cellLabel }}">Occupation</td><td style="{{ $cellValue }}">{{ $applicant['occupation'] ?? '-' }}</td></tr>
                                                    <tr><td style="{{ $cellLabel }}">Office ID</td><td style="{{ $cellValue }}">{{ $applicant['office_id'] ?? $applicant['staff_number'] ?? '-' }}</td></tr>
                                                    <tr><td style="{{ $cellLabel }}">Sector</td><td style="{{ $cellValue }}">{{ $label($applicant['sector'] ?? null) }}</td></tr>
                                                    <tr><td style="{{ $cellLabel }}">Monthly salary</td><td style="{{ $cellValue }}">{{ $money(data_get($applicant, 'bank_details.monthly_salary'), $currency) }}</td></tr>
                                                    <tr><td style="{{ $cellLabel }}">Salary bank</td><td style="{{ $cellValue }}">{{ data_get($applicant, 'bank_details.bank_name', '-') }}</td></tr>
                                                    <tr><td style="{{ $cellLabel }}">Employer address</td><td style="{{ $cellValue }}">{{ $applicant['employer_address'] ?? '-' }}</td></tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:0 0 14px;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="{{ $panel }}">
                            <tr><td style="padding:20px 22px 4px;font-size:16px;font-weight:800;">Flight and repayment summary</td></tr>
                            <tr>
                                <td style="padding:0 22px 20px;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                        <tr><td style="{{ $cellLabel }}">Route</td><td style="{{ $cellValue }}">{{ $route }}</td></tr>
                                        <tr><td style="{{ $cellLabel }}">Airline</td><td style="{{ $cellValue }}">{{ $flightInfo['airline'] ?? '-' }}</td></tr>
                                        <tr><td style="{{ $cellLabel }}">Cabin</td><td style="{{ $cellValue }}">{{ \App\Support\FlightDisplay::cabin($flightInfo ?? []) }}</td></tr>
                                        <tr><td style="{{ $cellLabel }}">Fare type</td><td style="{{ $cellValue }}">{{ $flightInfo['fareType'] ?? '-' }}</td></tr>
                                        <tr><td style="{{ $cellLabel }}">Refundable</td><td style="{{ $cellValue }}">{{ ($flightInfo['isRefundable'] ?? false) ? 'Yes' : 'No' }}</td></tr>
                                        <tr><td style="{{ $cellLabel }}">Ticket cost</td><td style="{{ $cellValue }}">{{ $money($ticketCost, $currency) }}</td></tr>
                                        <tr><td style="{{ $cellLabel }}">Down payment</td><td style="{{ $cellValue }}">{{ $money($downPayment, $currency) }}</td></tr>
                                        <tr><td style="{{ $cellLabel }}">Administration fee</td><td style="{{ $cellValue }}">{{ $money($administrationFee, $currency) }}</td></tr>
                                        <tr><td style="{{ $cellLabel }}">Insurance fee</td><td style="{{ $cellValue }}">{{ $money($insuranceFee, $currency) }}</td></tr>
                                        <tr><td style="{{ $cellLabel }}">Due after approval</td><td style="{{ $cellValue }}">{{ $money($upfrontPaymentTotal, $currency) }}</td></tr>
                                        <tr><td style="{{ $cellLabel }}">Loan amount</td><td style="{{ $cellValue }}">{{ $money($loanAmount, $currency) }}</td></tr>
                                        <tr><td style="{{ $cellLabel }}">Total payable</td><td style="{{ $cellValue }}">{{ $money($grandTotal, $currency) }}</td></tr>
                                        <tr><td style="{{ $cellLabel }}">Total interest</td><td style="{{ $cellValue }}">{{ $money($loanPlan['total_interest'] ?? 0, $currency) }}</td></tr>
                                        <tr><td style="{{ $cellLabel }}">Repayment plan</td><td style="{{ $cellValue }}">{{ $loanPlan['repayment_plan'] ?? '-' }}</td></tr>
                                        <tr><td style="{{ $cellLabel }}">Payment method</td><td style="{{ $cellValue }}">{{ $label($loanPlan['payment_method'] ?? null) }}</td></tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                @if($schedule !== [])
                    <tr>
                        <td style="padding:0 0 14px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="{{ $panel }}">
                                <tr><td style="padding:20px 22px 4px;font-size:16px;font-weight:800;">Repayment schedule</td></tr>
                                <tr>
                                    <td style="padding:0 22px 20px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #e6eaf2;border-radius:12px;overflow:hidden;">
                                            <tr style="background:#f8fafc;">
                                                <th align="left" style="padding:11px 12px;font-size:11px;text-transform:uppercase;color:#6b7280;">Instalment</th>
                                                <th align="left" style="padding:11px 12px;font-size:11px;text-transform:uppercase;color:#6b7280;">Due date</th>
                                                <th align="right" style="padding:11px 12px;font-size:11px;text-transform:uppercase;color:#6b7280;">Amount</th>
                                            </tr>
                                            @foreach($schedule as $index => $instalment)
                                                @php
                                                    $amount = $instalment['total'] ?? $instalment['amount'] ?? $instalment['principal'] ?? 0;
                                                    $dueDate = $instalment['dueDate'] ?? $instalment['due_date'] ?? $instalment['date'] ?? '-';
                                                @endphp
                                                <tr>
                                                    <td style="padding:12px;border-top:1px solid #eef2f7;font-size:13px;font-weight:700;color:#111827;">{{ $instalment['label'] ?? 'Payment ' . ($index + 1) }}</td>
                                                    <td style="padding:12px;border-top:1px solid #eef2f7;font-size:13px;color:#4b5563;">{{ $dueDate }}</td>
                                                    <td align="right" style="padding:12px;border-top:1px solid #eef2f7;font-size:13px;font-weight:800;color:#111827;">{{ $money($amount, $currency) }}</td>
                                                </tr>
                                            @endforeach
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                @endif

                <tr>
                    <td style="padding:0 0 14px;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="{{ $panel }}">
                            <tr><td style="padding:20px 22px 4px;font-size:16px;font-weight:800;">Attached documents</td></tr>
                            <tr>
                                <td style="padding:0 22px 20px;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                        @foreach($documents as $key => $name)
                                            @php
                                                $path = $uploadPaths[$key] ?? null;
                                                $attached = filled($path);
                                            @endphp
                                            <tr>
                                                <td style="{{ $cellLabel }}">{{ $name }}</td>
                                                <td style="{{ $cellValue }}">
                                                    <span style="display:inline-block;border-radius:999px;padding:5px 9px;background:{{ $attached ? '#ecfdf5' : '#fff7ed' }};color:{{ $attached ? '#047857' : '#b45309' }};font-size:12px;font-weight:800;">{{ $attached ? 'Attached' : 'Missing' }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </table>
                                    <div style="margin-top:12px;font-size:12px;line-height:18px;color:#6b7280;">Documents are attached to this email when available. Please verify authenticity before completing the provider decision.</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:0 0 14px;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef2ff;border:1px solid #c7d2fe;border-radius:16px;">
                            <tr>
                                <td style="padding:20px 22px;">
                                    <div style="font-size:15px;font-weight:800;color:#1f2378;">Provider action required</div>
                                    <div style="margin-top:8px;font-size:13px;line-height:20px;color:#374151;">
                                        Review the applicant profile, confirm the attached documents, complete your approval checks, and reply with the provider decision, reference, and any follow-up required. Applicant contact: <strong>{{ $applicant['email'] ?? '-' }}</strong>.
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td align="center" style="padding:12px 20px 4px;font-size:12px;line-height:18px;color:#6b7280;">
                        This message was sent by Travelwheel TravelFlex. Please handle applicant and document data securely.<br>
                        Travelwheel Limited | support@travelwheel.ng | +2348056265618
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
