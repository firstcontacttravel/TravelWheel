{{-- resources/views/livewire/pages/flight/flight-travelflex-application.blade.php --}}
@component('layouts.app', ['title' => 'TravelFlex - Loan Application'])

@php
    $contact    = session('bookingContact', []);
    $passengers = session('bookingPassengers', []);
    $primary    = collect($passengers)->firstWhere('is_primary', true) ?? ($passengers[0] ?? []);
    $prefillTitle = $primary['title'] ?? '';
    $prefillSurname = $primary['last_name'] ?? '';
    $prefillFirstName = $primary['first_name'] ?? '';
    $prefillOtherName = $primary['middle_name'] ?? ($primary['other_name'] ?? '');
    $prefillGender = match (strtoupper((string) ($primary['gender'] ?? ''))) {
        'F', 'FEMALE' => 'female',
        'M', 'MALE' => 'male',
        default => '',
    };
    $prefillDob = $primary['dob'] ?? '';
    $prefillPassportNo = $primary['passport_no'] ?? '';
    $prefillPassportExpiry = $primary['passport_exp'] ?? '';
    $prefillName = trim(collect([$prefillTitle, $prefillSurname, $prefillFirstName, $prefillOtherName])->filter()->implode(' '));
    $prefillEmail = $contact['email'] ?? '';
    $prefillPhone = $contact['phone'] ?? '';

    $tfPlan       = session('travelFlexPlan', []);
    $ticketCost   = (float) ($tfPlan['ticket_cost'] ?? 0);
    $downPayment  = (float) ($tfPlan['down_payment'] ?? 0);
    $loanAmount   = (float) ($tfPlan['loan_amount'] ?? $tfPlan['remaining_balance'] ?? max(0, $ticketCost - $downPayment));
    $administrationFee = (float) ($tfPlan['administration_fee'] ?? 0);
    $administrationFeePercent = (float) ($tfPlan['administration_fee_percent'] ?? 1);
    $insuranceFee = (float) ($tfPlan['insurance_fee'] ?? 0);
    $insuranceFeePercent = (float) ($tfPlan['insurance_fee_percent'] ?? 1.5);
    $upfrontPaymentTotal = (float) ($tfPlan['upfront_payment_total'] ?? ($downPayment + $administrationFee + $insuranceFee));
    $repayPlan    = $tfPlan['repayment_plan'] ?? '';

    $flight       = session('bookingFlight.flight') ?? session('bookingFlight', []);
    $segments     = $flight['segments'] ?? [];
    $multiLegs    = $flight['multiLegs'] ?? [];
    $isMulti      = count($multiLegs) > 0;
    $firstSeg     = $segments[0] ?? [];
    $lastSeg      = !empty($segments) ? $segments[count($segments)-1] : [];
    $currency     = $flight['currency'] ?? 'NGN';
    $sym          = match($currency) { 'NGN' => html_entity_decode('&#8358;', ENT_QUOTES, 'UTF-8'), 'USD' => '$', 'GBP' => html_entity_decode('&pound;', ENT_QUOTES, 'UTF-8'), 'EUR' => html_entity_decode('&euro;', ENT_QUOTES, 'UTF-8'), default => $currency.' ' };
    $fmt          = fn($v) => $sym . number_format((float)$v, 2);
    $errors       = $errors ?? new \Illuminate\Support\MessageBag();
    $errorKeys    = collect($errors->keys());
    $initialStep  = $errorKeys->intersect([
        'employer_name', 'occupation', 'employer_address', 'job_description', 'office_id', 'sector', 'ippis_number',
        'monthly_salary', 'salary_account_number', 'bank_name', 'social_media_platform', 'social_media_handle',
        'government_id_type', 'next_of_kin_surname',
        'next_of_kin_first_name', 'next_of_kin_relationship', 'next_of_kin_address', 'next_of_kin_phone_primary',
    ])->isNotEmpty() ? 2 : ($errorKeys->intersect([
        'valid_id', 'passport_photo', 'work_id_card', 'employment_letter', 'bank_statements',
        'representative_valid_id', 'cac_status_report', 'share_certificate', 'memart', 'register_of_members',
        'shareholders_agreement', 'return_of_allotment', 'certificate_of_incorporation', 'board_resolution',
        'company_bank_statement', 'tin_certificate',
    ])->isNotEmpty() ? 3 : ($errorKeys->intersect([
        'fast_credit_agreement', 'digital_signature', 'digital_signature_image', 'witness_full_name',
        'witness_signature_image', 'witness_declaration',
    ])->isNotEmpty() ? 4 : 1));
    $routeLines   = [];
    if ($isMulti) {
        foreach ($multiLegs as $leg) {
            $routeLines[] = ($leg['from'] ?? '') . ' → ' . ($leg['to'] ?? '');
        }
    }
@endphp

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
        --navy: #0a1940; --blue: #1d4ed8; --blue-lt: #eff6ff; --blue-md: #bfdbfe;
        --indigo: #4338ca; --purple: #7c3aed; --green: #059669; --green-lt: #f0fdf4;
        --amber: #d97706; --amber-lt: #fff7ed; --red: #dc2626; --red-lt: #fef2f2;
        --gray-50: #f8fafc; --gray-100: #f1f5f9; --gray-200: #e2e8f0; --gray-300: #cbd5e1;
        --gray-400: #94a3b8; --gray-500: #64748b; --gray-700: #334155; --gray-900: #0f172a;
        --font: 'Plus Jakarta Sans', sans-serif; --mono: 'DM Mono', monospace;
    }
    body { font-family: var(--font); background: var(--gray-50); color: var(--gray-900); font-size: 14px; margin-top: 110px; }
    .tfa-wrap { max-width: 800px; margin: 0 auto; padding: 28px 16px 80px; }
    /* Hero */
    .tfa-hero { background: linear-gradient(135deg, var(--navy) 0%, var(--indigo) 60%, var(--purple) 100%); border-radius: 14px; padding: 24px 26px; margin-bottom: 22px; color: #fff; }
    .tfa-hero-badge { display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; background: rgba(255,255,255,.15); border-radius: 999px; font-size: 11px; font-weight: 700; margin-bottom: 8px; }
    .tfa-hero-title { font-size: 20px; font-weight: 800; margin-bottom: 4px; }
    .tfa-hero-sub { font-size: 13px; opacity: .85; line-height: 1.6; }
    /* Card */
    .tfa-card { background: #fff; border: 1px solid var(--gray-200); border-radius: 12px; padding: 24px; box-shadow: 0 1px 4px rgba(0,0,0,.06); margin-bottom: 18px; }
    .tfa-section-title { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: var(--gray-400); margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid var(--gray-100); }
    /* Form grid */
    .tfa-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .tfa-full { grid-column: 1 / -1; }
    .tfa-field { display: flex; flex-direction: column; gap: 5px; }
    .tfa-label { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--gray-400); }
    .tfa-req { color: var(--red); margin-left: 2px; }
    .tfa-input, .tfa-select, .tfa-textarea {
        height: 44px; padding: 0 12px; border: 1.5px solid var(--gray-200); border-radius: 9px;
        font-size: 14px; color: var(--gray-900); background: var(--gray-50); outline: none;
        font-family: var(--font); transition: border-color .15s; width: 100%;
    }
    .tfa-textarea { height: auto; padding: 10px 12px; resize: vertical; min-height: 80px; }
    .tfa-input:focus, .tfa-select:focus, .tfa-textarea:focus { border-color: var(--blue); background: #fff; box-shadow: 0 0 0 3px rgba(59,130,246,.1); }
    .tfa-input[readonly] { background: #eef2f7; color: var(--gray-500); cursor: not-allowed; }
    .tfa-input.error, .tfa-select.error, .tfa-textarea.error { border-color: var(--red); }
    .tfa-error { font-size: 11px; color: var(--red); margin-top: 2px; }
    .tfa-hint { font-size: 11px; color: var(--gray-400); margin-top: 2px; }
    /* File upload */
    .tfa-file-wrap { border: 2px dashed var(--gray-200); border-radius: 10px; padding: 16px; text-align: center; cursor: pointer; transition: all .15s; background: var(--gray-50); }
    .tfa-file-wrap:hover { border-color: var(--blue); background: var(--blue-lt); }
    .tfa-file-wrap input[type=file] { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
    .tfa-file-label { font-size: 13px; font-weight: 600; color: var(--gray-500); cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 6px; }
    .tfa-file-icon { font-size: 28px; }
    .tfa-file-name { font-size: 11.5px; color: var(--green); font-weight: 700; margin-top: 4px; display: none; }
    /* Summary strip */
    .tfa-loan-strip { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; padding: 14px 18px; background: linear-gradient(135deg, var(--navy) 0%, var(--indigo) 100%); border-radius: 10px; margin-bottom: 16px; }
    .tfa-loan-item { text-align: center; }
    .tfa-loan-lbl { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: rgba(255,255,255,.65); margin-bottom: 4px; }
    .tfa-loan-val { font-size: 15px; font-weight: 800; color: #fff; font-family: var(--mono); }
    /* Notice */
    .tfa-notice { display: flex; align-items: flex-start; gap: 9px; padding: 11px 14px; border-radius: 9px; font-size: 12.5px; margin-bottom: 16px; }
    .tfa-notice.warn { background: var(--amber-lt); color: var(--amber); border: 1px solid #fed7aa; }
    .tfa-notice.info { background: var(--blue-lt); color: var(--blue); border: 1px solid var(--blue-md); }
    /* Buttons */
    .tfa-btn-row { display: flex; gap: 10px; margin-top: 22px; flex-wrap: wrap; }
    .tfa-btn-primary { height: 50px; padding: 0 30px; background: linear-gradient(135deg, var(--indigo) 0%, var(--purple) 100%); color: #fff; border: none; border-radius: 11px; font-size: 14px; font-weight: 800; cursor: pointer; font-family: var(--font); display: inline-flex; align-items: center; gap: 8px; transition: all .15s; }
    .tfa-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(124,58,237,.35); }
    .tfa-btn-ghost { height: 50px; padding: 0 22px; background: #fff; border: 1.5px solid var(--gray-200); border-radius: 11px; font-size: 13.5px; font-weight: 700; color: var(--gray-700); display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: all .15s; cursor: pointer; }
    .tfa-btn-ghost:hover { background: var(--gray-50); border-color: var(--gray-400); }
    @media(max-width:640px){ .tfa-grid { grid-template-columns: 1fr; } .tfa-full { grid-column: 1; } .tfa-wrap { padding: 12px 10px 60px; } }
</style>

<style>
    :root {
        --tfa-brand: #39328f;
        --tfa-brand-700: #2f287c;
        --tfa-green: #049a63;
        --tfa-green-soft: #eefaf4;
        --tfa-ink: #101828;
        --tfa-muted: #667085;
        --tfa-subtle: #98a2b3;
        --tfa-line: #e6e9f0;
        --tfa-soft: #f7f8fb;
        --tfa-card: #ffffff;
        --navy: var(--tfa-ink);
        --blue: var(--tfa-brand);
        --blue-lt: #f5f7ff;
        --blue-md: rgba(57,50,143,.16);
        --indigo: var(--tfa-brand);
        --purple: var(--tfa-brand);
        --green: var(--tfa-green);
        --green-lt: var(--tfa-green-soft);
        --gray-50: var(--tfa-soft);
        --gray-100: #eef1f6;
        --gray-200: var(--tfa-line);
        --gray-300: #cfd4df;
        --gray-400: var(--tfa-subtle);
        --gray-500: var(--tfa-muted);
        --gray-700: #344054;
        --gray-900: var(--tfa-ink);
    }
    body { background: #f7f8fb; color: var(--tfa-ink); }
    .tfa-wrap { max-width: 1040px; padding: 24px 18px 80px; }
    .tfa-hero {
        background: #fff;
        border: 1px solid var(--tfa-line);
        border-radius: 8px;
        padding: 22px;
        color: var(--tfa-ink);
        margin-bottom: 16px;
        box-shadow: 0 14px 36px rgba(16,24,40,.06);
    }
    .tfa-hero-badge {
        background: #f5f7ff;
        border: 1px solid rgba(57,50,143,.16);
        color: var(--tfa-brand);
        border-radius: 999px;
        min-height: 30px;
        padding: 6px 10px;
        font-size: 11px;
        letter-spacing: .02em;
    }
    .tfa-hero-badge svg { flex: 0 0 auto; }
    .tfa-hero-title { color: var(--tfa-ink); font-size: clamp(22px, 2.3vw, 32px); line-height: 1.14; letter-spacing: 0; margin-bottom: 8px; }
    .tfa-hero-sub { color: var(--tfa-muted); opacity: 1; font-size: 14px; max-width: 740px; }
    .tfa-loan-strip {
        background: #fff;
        border: 1px solid var(--tfa-line);
        border-radius: 8px;
        box-shadow: 0 12px 32px rgba(16,24,40,.055);
        padding: 16px;
        gap: 12px;
        align-items: stretch;
    }
    .tfa-loan-item {
        flex: 1 1 190px;
        min-width: 0;
        text-align: left;
        background: #fbfcfe;
        border: 1px solid #eef1f6;
        border-radius: 8px;
        padding: 12px;
    }
    .tfa-loan-lbl { color: var(--tfa-muted); font-size: 10.5px; }
    .tfa-loan-val { color: var(--tfa-ink); font-size: 14px; overflow-wrap: anywhere; }
    .tfa-card {
        border: 1px solid var(--tfa-line);
        border-radius: 8px;
        padding: 22px;
        box-shadow: 0 12px 32px rgba(16,24,40,.055);
    }
    .tfa-section-title {
        display: flex;
        align-items: center;
        gap: 9px;
        color: var(--tfa-ink);
        font-size: 12px;
        letter-spacing: .05em;
        border-bottom: 1px solid #eef1f6;
    }
    .tfa-section-title svg { color: var(--tfa-brand); flex: 0 0 auto; }
    .tfa-label { color: var(--tfa-muted); font-size: 11px; letter-spacing: .045em; }
    .tfa-input, .tfa-select, .tfa-textarea {
        border: 1px solid var(--tfa-line);
        border-radius: 8px;
        background: #fff;
        color: var(--tfa-ink);
        transition: border-color .18s, box-shadow .18s, background .18s;
    }
    .tfa-input:focus, .tfa-select:focus, .tfa-textarea:focus { border-color: var(--tfa-brand); box-shadow: 0 0 0 4px rgba(57,50,143,.08); }
    .tfa-input[readonly] { background: var(--tfa-soft); color: var(--tfa-muted); }
    .tfa-hint { color: var(--tfa-subtle); line-height: 1.4; }
    .tfa-file-wrap {
        border: 1px dashed #cfd4df;
        border-radius: 8px;
        background: #fbfcfe;
        text-align: left;
        display: flex;
        flex-direction: column;
        min-height: 176px;
        position: relative;
        transition: border-color .18s, box-shadow .18s, background .18s, transform .18s;
    }
    .tfa-file-wrap:hover { border-color: rgba(57,50,143,.42); background: #fff; box-shadow: 0 12px 24px rgba(16,24,40,.055); transform: translateY(-1px); }
    .tfa-file-wrap:focus-within { border-color: var(--tfa-brand); box-shadow: 0 0 0 4px rgba(57,50,143,.08); background:#fff; }
    .tfa-file-wrap.has-file { border-style: solid; border-color: rgba(4,154,99,.35); background: #fbfffd; }
    .tfa-file-label { align-items: flex-start; color: var(--tfa-muted); line-height: 1.45; flex: 1; width: 100%; }
    .tfa-file-label strong { color: var(--tfa-ink); }
    .tfa-file-icon {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        background: #f5f7ff;
        color: var(--tfa-brand);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0;
        margin-bottom: 2px;
    }
    .tfa-file-name {
        color: var(--tfa-green);
        display: none;
        width: 100%;
        margin-top: 12px;
        padding: 8px 10px;
        border-radius: 8px;
        background: var(--tfa-green-soft);
        font-size: 11.5px;
        line-height: 1.35;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .tfa-file-name.visible { display: block; }
    .tfa-doc-grid {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 14px;
        align-items: stretch;
    }
    .tfa-doc-field { grid-column: span 2; min-width: 0; }
    .tfa-doc-field.tfa-doc-wide { grid-column: span 3; }
    .tfa-notice { border-radius: 8px; line-height: 1.55; }
    .tfa-notice.info { background: #f5f7ff; color: var(--tfa-brand); border: 1px solid rgba(57,50,143,.16); }
    .tfa-btn-primary, .tfa-btn-ghost {
        border-radius: 8px;
        min-height: 50px;
        transition: transform .18s, box-shadow .18s, background .18s, border-color .18s;
    }
    .tfa-btn-primary { background: var(--tfa-brand); box-shadow: 0 10px 22px rgba(57,50,143,.18); }
    .tfa-btn-primary:hover { background: var(--tfa-brand-700); box-shadow: 0 12px 26px rgba(57,50,143,.2); }
    .tfa-btn-ghost { border: 1px solid var(--tfa-line); color: var(--tfa-muted); }
    .tfa-btn-ghost:hover { border-color: #cfd4df; color: var(--tfa-ink); background: var(--tfa-soft); }
    [x-cloak] { display: none !important; }
    .tfa-wizard {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 16px;
    }
    .tfa-wizard-step {
        border: 1px solid var(--tfa-line);
        border-radius: 8px;
        background: #fff;
        padding: 12px;
        text-align: left;
        color: var(--tfa-muted);
        cursor: pointer;
        transition: border-color .18s, box-shadow .18s, background .18s;
    }
    .tfa-wizard-step strong { display: block; color: var(--tfa-ink); font-size: 13px; margin-bottom: 2px; }
    .tfa-wizard-step span { font-size: 11px; line-height: 1.35; }
    .tfa-wizard-step.is-active { border-color: rgba(57,50,143,.55); box-shadow: 0 0 0 4px rgba(57,50,143,.08); background: #fbfbff; }
    .tfa-step-help {
        margin-bottom: 16px;
        border: 1px solid rgba(57,50,143,.14);
        border-radius: 8px;
        background: #fbfbff;
        padding: 12px 14px;
        color: var(--tfa-muted);
        font-size: 13px;
        line-height: 1.5;
    }
    .tfa-progress-row { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 16px; }
    .tfa-progress-pill { display: inline-flex; align-items: center; gap: 7px; min-height: 30px; padding: 6px 10px; border-radius: 999px; background: var(--tfa-soft); color: var(--tfa-muted); font-size: 12px; font-weight: 700; }
    .tfa-progress-pill strong { color: var(--tfa-brand); }
    .tfa-pay-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
    .tfa-pay-card { position: relative; display: flex; gap: 13px; align-items: flex-start; min-height: 132px; padding: 16px; border: 1px solid var(--tfa-line); border-radius: 8px; background: #fbfcfe; cursor: pointer; transition: border-color .18s, box-shadow .18s, background .18s, transform .18s; }
    .tfa-pay-card:hover { background: #fff; border-color: rgba(57,50,143,.34); box-shadow: 0 12px 26px rgba(16,24,40,.055); transform: translateY(-1px); }
    .tfa-pay-card input { position: absolute; opacity: 0; pointer-events: none; }
    .tfa-pay-icon { width: 40px; height: 40px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; background: #f5f7ff; color: var(--tfa-brand); }
    .tfa-pay-title { display: block; font-size: 14px; font-weight: 800; color: var(--tfa-ink); }
    .tfa-pay-copy { display: block; margin-top: 5px; font-size: 12.5px; line-height: 1.55; color: var(--tfa-muted); }
    .tfa-pay-radio { margin-left: auto; width: 20px; height: 20px; border-radius: 999px; border: 2px solid #cfd4df; flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center; }
    .tfa-pay-radio::after { content: ""; width: 8px; height: 8px; border-radius: 999px; background: #fff; transform: scale(0); transition: transform .16s; }
    .tfa-pay-card:has(input:checked) { background: #fff; border-color: rgba(57,50,143,.55); box-shadow: 0 0 0 4px rgba(57,50,143,.08); }
    .tfa-pay-card:has(input:checked) .tfa-pay-radio { border-color: var(--tfa-brand); background: var(--tfa-brand); }
    .tfa-pay-card:has(input:checked) .tfa-pay-radio::after { transform: scale(1); }
    .tfa-signature-box {
        border: 1px solid var(--tfa-line);
        border-radius: 8px;
        background: #fff;
        overflow: hidden;
        box-shadow: inset 0 0 0 1px rgba(16,24,40,.015);
    }
    .tfa-signature-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 12px;
        border-bottom: 1px solid #eef1f6;
        background: #fbfcfe;
    }
    .tfa-signature-toolbar span { color: var(--tfa-muted); font-size: 12px; line-height: 1.4; }
    .tfa-signature-clear {
        border: 1px solid var(--tfa-line);
        background: #fff;
        color: var(--tfa-muted);
        border-radius: 7px;
        min-height: 34px;
        padding: 0 12px;
        font-size: 12px;
        font-weight: 800;
        cursor: pointer;
        font-family: var(--font);
    }
    .tfa-signature-clear:hover { background: var(--tfa-soft); color: var(--tfa-ink); }
    .tfa-signature-canvas {
        display: block;
        width: 100%;
        height: 190px;
        touch-action: none;
        cursor: crosshair;
        background:
            linear-gradient(#fff, #fff) padding-box,
            repeating-linear-gradient(0deg, transparent 0 38px, #f2f4f7 39px, transparent 40px);
    }
    .tfa-agreement-box {
        max-height: 360px;
        overflow: auto;
        border: 1px solid var(--tfa-line);
        border-radius: 8px;
        background: #fff;
        padding: 16px;
        color: var(--tfa-muted);
        line-height: 1.6;
        font-size: 12.5px;
    }
    .tfa-agreement-box h4 {
        color: var(--tfa-ink);
        font-size: 12px;
        font-weight: 800;
        margin: 14px 0 6px;
    }
    .tfa-agreement-box h4:first-child { margin-top: 0; }
    .tfa-agreement-box p { margin: 0 0 8px; }
    .pac-container { z-index: 99999; }
    @media(max-width:640px) {
        body { margin-top: 0; }
        .tfa-wrap { padding: 14px 12px 64px; }
        .tfa-hero, .tfa-card { padding: 16px; }
        .tfa-doc-grid { grid-template-columns: 1fr; }
        .tfa-doc-field, .tfa-doc-field.tfa-doc-wide { grid-column: 1; }
        .tfa-file-wrap { min-height: 154px; }
        .tfa-pay-grid { grid-template-columns: 1fr; }
        .tfa-wizard { grid-template-columns: 1fr 1fr; }
        .tfa-wizard-step { padding: 10px; }
        .tfa-btn-row { flex-direction: column; }
        .tfa-btn-row > * { width: 100%; justify-content: center; }
    }
</style>

<div class="tfa-wrap">

    {{-- Hero --}}
    <div class="tfa-hero">
        <div class="tfa-hero-badge">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3h6l1 2h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h3l1-2Z"/><path d="M9 13h6"/><path d="M9 17h4"/></svg>
            TravelFlex Loan Application
        </div>
        <div class="tfa-hero-title">Complete Your Loan Application</div>
        <div class="tfa-hero-sub">Please fill in all required fields accurately. Your application will be reviewed by our lending partner. Required documents must be uploaded to process your application.</div>
        <div class="tfa-progress-row">
            <div class="tfa-progress-pill"><strong>1</strong> Applicant details</div>
            <div class="tfa-progress-pill"><strong>2</strong> Employment</div>
            <div class="tfa-progress-pill"><strong>3</strong> Documents</div>
            <div class="tfa-progress-pill"><strong>4</strong> Payment</div>
        </div>
    </div>

    {{-- Loan Summary Strip --}}
    <div class="tfa-loan-strip">
        <div class="tfa-loan-item">
            <div class="tfa-loan-lbl">Flight</div>
            <div class="tfa-loan-val" style="font-family:var(--font);font-size:13px;line-height:1.45;">
                @if($isMulti)
                    @foreach($routeLines as $routeLine)
                        <div>{{ $routeLine }}</div>
                    @endforeach
                @else
                    {{ ($firstSeg['from']??'') }} &rarr; {{ ($lastSeg['to']??'') }}
                @endif
            </div>
        </div>
        <div class="tfa-loan-item">
            <div class="tfa-loan-lbl">Total Loan Amount</div>
            <div class="tfa-loan-val">{{ $fmt($loanAmount) }}</div>
        </div>
        <div class="tfa-loan-item">
            <div class="tfa-loan-lbl">Due After Approval</div>
            <div class="tfa-loan-val">{{ $fmt($upfrontPaymentTotal) }}</div>
        </div>
        <div class="tfa-loan-item">
            <div class="tfa-loan-lbl">Admin Fee ({{ rtrim(rtrim(number_format($administrationFeePercent, 2), '0'), '.') }}%)</div>
            <div class="tfa-loan-val">{{ $fmt($administrationFee) }}</div>
        </div>
        <div class="tfa-loan-item">
            <div class="tfa-loan-lbl">Insurance ({{ rtrim(rtrim(number_format($insuranceFeePercent, 2), '0'), '.') }}%)</div>
            <div class="tfa-loan-val">{{ $fmt($insuranceFee) }}</div>
        </div>
        <div class="tfa-loan-item">
            <div class="tfa-loan-lbl">Repayment Plan</div>
            <div class="tfa-loan-val">{{ $repayPlan }}</div>
        </div>
    </div>

    <div class="tfa-notice warn">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>All information provided is subject to verification. Submitting false information may result in rejection and legal action. Fields marked <strong>*</strong> are required.</span>
    </div>

    @if($errors->any())
    <div class="tfa-notice" style="background:var(--red-lt);color:var(--red);border:1px solid #fca5a5;margin-bottom:16px;">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>Please correct the errors below before submitting.</span>
    </div>
    @endif

    <form method="POST" action="{{ route('flights.travelflex.submit-application') }}"
          enctype="multipart/form-data" id="tfa-form"
          x-data="{ applicantType: @js(old('applicant_type', 'individual')), step: @js($initialStep) }">
        @csrf

        <div class="tfa-wizard" aria-label="Application steps">
            <button type="button" class="tfa-wizard-step" :class="{ 'is-active': step === 1 }" @click="step = 1">
                <strong>1. Applicant</strong><span>Basic applicant and contact details</span>
            </button>
            <button type="button" class="tfa-wizard-step" :class="{ 'is-active': step === 2 }" @click="step = 2">
                <strong>2. Profile</strong><span>Employment, banking and next of kin</span>
            </button>
            <button type="button" class="tfa-wizard-step" :class="{ 'is-active': step === 3 }" @click="step = 3">
                <strong>3. Documents</strong><span>Upload required supporting files</span>
            </button>
            <button type="button" class="tfa-wizard-step" :class="{ 'is-active': step === 4 }" @click="step = 4">
                <strong>4. Agreement</strong><span>Review declaration and submit</span>
            </button>
        </div>

        <div class="tfa-step-help">
            Complete one section at a time. You can move between sections before submitting, and the final submit button only appears on the agreement step.
        </div>

        <div class="tfa-card" x-show="step === 1" x-cloak>
            <div class="tfa-section-title">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h18"/><path d="M3 12h18"/><path d="M3 17h18"/></svg>
                Applicant Type
            </div>
            <div class="tfa-pay-grid">
                <label class="tfa-pay-card">
                    <input type="radio" name="applicant_type" value="individual" x-model="applicantType">
                    <span><span class="tfa-pay-title">Individual</span><span class="tfa-pay-copy">Personal salary or payroll-backed Fast Credit application.</span></span>
                    <span class="tfa-pay-radio"></span>
                </label>
                <label class="tfa-pay-card">
                    <input type="radio" name="applicant_type" value="company" x-model="applicantType">
                    <span><span class="tfa-pay-title">Business owner</span><span class="tfa-pay-copy">I own and manage the business applying for TravelFlex.</span></span>
                    <span class="tfa-pay-radio"></span>
                </label>
            </div>
            @error('applicant_type') <span class="tfa-error">{{ $message }}</span> @enderror
        </div>

        {{-- Section 1: Personal Information --}}
        <div class="tfa-card" x-show="step === 1" x-cloak>
            <div class="tfa-section-title">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                Personal Information
            </div>
            <div class="tfa-grid">
                <div class="tfa-field">
                    <div class="tfa-label">Title <span class="tfa-req">*</span></div>
                    <input class="tfa-input {{ $errors->has('title') ? 'error' : '' }}" type="text" name="title" value="{{ old('title', $prefillTitle) }}" placeholder="Mr, Mrs, Ms, Dr">
                    @error('title') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field">
                    <div class="tfa-label">Surname <span class="tfa-req">*</span></div>
                    <input class="tfa-input {{ $errors->has('surname') ? 'error' : '' }}" type="text" name="surname" value="{{ old('surname', $prefillSurname) }}" placeholder="Surname">
                    @error('surname') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field">
                    <div class="tfa-label">First Name <span class="tfa-req">*</span></div>
                    <input class="tfa-input {{ $errors->has('first_name') ? 'error' : '' }}" type="text" name="first_name" value="{{ old('first_name', $prefillFirstName) }}" placeholder="First name">
                    @error('first_name') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field">
                    <div class="tfa-label">Other Name</div>
                    <input class="tfa-input {{ $errors->has('other_name') ? 'error' : '' }}" type="text" name="other_name" value="{{ old('other_name', $prefillOtherName) }}" placeholder="Other name">
                    @error('other_name') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field tfa-full">
                    <div class="tfa-label"><span x-text="applicantType === 'company' ? 'Representative Residential Address' : 'Residential Address'"></span> <span class="tfa-req">*</span></div>
                    <input class="tfa-input {{ $errors->has('home_address') ? 'error' : '' }}"
                           type="text" name="home_address" value="{{ old('home_address') }}"
                           placeholder="Start typing and select a precise address" autocomplete="off"
                           data-google-address data-place-target="home_address_place_id">
                    <input type="hidden" name="home_address_place_id" id="home_address_place_id" value="{{ old('home_address_place_id') }}">
                    @error('home_address') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field">
                    <div class="tfa-label">Email Address <span class="tfa-req">*</span></div>
                    <input class="tfa-input {{ $errors->has('email') ? 'error' : '' }}"
                           type="email" name="email" value="{{ old('email', $prefillEmail) }}"
                           placeholder="your@email.com">
                    @error('email') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field">
                    <div class="tfa-label">Phone Number 1 <span class="tfa-req">*</span></div>
                    <input class="tfa-input {{ $errors->has('phone_primary') ? 'error' : '' }}" type="text" name="phone_primary" value="{{ old('phone_primary', $prefillPhone) }}" placeholder="+234...">
                    @error('phone_primary') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field">
                    <div class="tfa-label">Phone Number 2</div>
                    <input class="tfa-input {{ $errors->has('phone_secondary') ? 'error' : '' }}" type="text" name="phone_secondary" value="{{ old('phone_secondary') }}" placeholder="Optional alternate number">
                    @error('phone_secondary') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field">
                    <div class="tfa-label">Bank Verification Number (BVN) <span class="tfa-req">*</span></div>
                    <input class="tfa-input {{ $errors->has('bvn') ? 'error' : '' }}"
                           type="text" name="bvn" value="{{ old('bvn') }}"
                           maxlength="11" inputmode="numeric" placeholder="11-digit BVN">
                    <div class="tfa-hint">Your BVN is used for credit verification only</div>
                    @error('bvn') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field">
                    <div class="tfa-label">National Identification Number (NIN) <span class="tfa-req">*</span></div>
                    <input class="tfa-input {{ $errors->has('nin') ? 'error' : '' }}" type="text" name="nin" value="{{ old('nin') }}" maxlength="11" inputmode="numeric" placeholder="11-digit NIN">
                    @error('nin') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field">
                    <div class="tfa-label">Marital Status <span class="tfa-req">*</span></div>
                    <select class="tfa-input {{ $errors->has('marital_status') ? 'error' : '' }}" name="marital_status">
                        <option value="">Select status</option>
                        @foreach(['single' => 'Single', 'married' => 'Married', 'divorced' => 'Divorced', 'separated' => 'Separated'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('marital_status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('marital_status') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field">
                    <div class="tfa-label">Gender <span class="tfa-req">*</span></div>
                    <select class="tfa-input {{ $errors->has('gender') ? 'error' : '' }}" name="gender">
                        <option value="">Select gender</option>
                        <option value="female" @selected(old('gender', $prefillGender) === 'female')>Female</option>
                        <option value="male" @selected(old('gender', $prefillGender) === 'male')>Male</option>
                    </select>
                    @error('gender') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field">
                    <div class="tfa-label">Date of Birth <span class="tfa-req">*</span></div>
                    <input class="tfa-input {{ $errors->has('date_of_birth') ? 'error' : '' }}" type="date" name="date_of_birth" value="{{ old('date_of_birth', $prefillDob) }}">
                    @error('date_of_birth') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field">
                    <div class="tfa-label">Passport Number <span class="tfa-req">*</span></div>
                    <input class="tfa-input {{ $errors->has('passport_number') ? 'error' : '' }}" type="text" name="passport_number" value="{{ old('passport_number', $prefillPassportNo) }}">
                    @error('passport_number') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field">
                    <div class="tfa-label">Passport Expiry Date <span class="tfa-req">*</span></div>
                    <input class="tfa-input {{ $errors->has('passport_expiry_date') ? 'error' : '' }}" type="date" name="passport_expiry_date" value="{{ old('passport_expiry_date', $prefillPassportExpiry) }}">
                    @error('passport_expiry_date') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field">
                    <div class="tfa-label">Government ID Type <span class="tfa-req">*</span></div>
                    <select class="tfa-input {{ $errors->has('government_id_type') ? 'error' : '' }}" name="government_id_type">
                        <option value="">Select the ID you are uploading</option>
                        @foreach(['national_id' => 'National ID', 'drivers_licence' => "Driver's Licence", 'international_passport' => 'International Passport', 'voters_card' => "Voter's Card"] as $value => $label)
                            <option value="{{ $value }}" @selected(old('government_id_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('government_id_type') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        {{-- Section 2: Employment Information --}}
        <div class="tfa-card" x-show="step === 2" x-cloak>
            <div class="tfa-section-title">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M3 12h18"/></svg>
                Employment and Banking Information
            </div>
            <div class="tfa-grid">
                <div class="tfa-field">
                    <div class="tfa-label"><span x-text="applicantType === 'company' ? 'Your Company Name' : 'Employer Company Name'"></span> <span class="tfa-req">*</span></div>
                    <input class="tfa-input {{ $errors->has('employer_name') ? 'error' : '' }}"
                           type="text" name="employer_name" value="{{ old('employer_name') }}"
                           :placeholder="applicantType === 'company' ? 'Name of your company' : 'Name of your employer'">
                    @error('employer_name') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field">
                    <div class="tfa-label">Occupation / Job Title <span class="tfa-req">*</span></div>
                    <input class="tfa-input {{ $errors->has('occupation') ? 'error' : '' }}"
                           type="text" name="occupation" value="{{ old('occupation') }}"
                           placeholder="e.g. Software Engineer">
                    @error('occupation') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field tfa-full">
                    <div class="tfa-label">Employer Full Address <span class="tfa-req">*</span></div>
                    <input class="tfa-input {{ $errors->has('employer_address') ? 'error' : '' }}"
                           type="text" name="employer_address" value="{{ old('employer_address') }}"
                           placeholder="Start typing and select employer address" autocomplete="off"
                           data-google-address data-place-target="employer_address_place_id">
                    <input type="hidden" name="employer_address_place_id" id="employer_address_place_id" value="{{ old('employer_address_place_id') }}">
                    @error('employer_address') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field tfa-full">
                    <div class="tfa-label">Job Description <span class="tfa-req">*</span></div>
                    <textarea class="tfa-textarea {{ $errors->has('job_description') ? 'error' : '' }}"
                              name="job_description" placeholder="Briefly describe your role and responsibilities"
                              rows="2">{{ old('job_description') }}</textarea>
                    @error('job_description') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field">
                    <div class="tfa-label">Office ID <span class="tfa-req">*</span></div>
                    <input class="tfa-input {{ $errors->has('office_id') ? 'error' : '' }}"
                           type="text" name="office_id" value="{{ old('office_id') }}"
                           placeholder="Your work or company ID number">
                    @error('office_id') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field">
                    <div class="tfa-label">Sector <span class="tfa-req">*</span></div>
                    <select class="tfa-input {{ $errors->has('sector') ? 'error' : '' }}" name="sector" :disabled="applicantType === 'company'">
                        <option value="">Select sector</option>
                        <option value="private" @selected(old('sector') === 'private')>Private</option>
                        <option value="public" @selected(old('sector') === 'public')>Public</option>
                    </select>
                    <input type="hidden" name="sector" value="private" :disabled="applicantType !== 'company'">
                    <div class="tfa-hint" x-show="applicantType === 'company'">Business owners are recorded as private sector.</div>
                    @error('sector') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field">
                    <div class="tfa-label">IPPIS Number <small>Public sector only</small></div>
                    <input class="tfa-input {{ $errors->has('ippis_number') ? 'error' : '' }}" type="text" name="ippis_number" value="{{ old('ippis_number') }}" placeholder="IPPIS number">
                    @error('ippis_number') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field">
                    <div class="tfa-label"><span x-text="applicantType === 'company' ? 'Personal Monthly Income / Draw' : 'Monthly Salary Amount'"></span> <span class="tfa-req">*</span></div>
                    <input class="tfa-input {{ $errors->has('monthly_salary') ? 'error' : '' }}" type="number" min="0" step="0.01" name="monthly_salary" value="{{ old('monthly_salary') }}" :placeholder="applicantType === 'company' ? 'Personal monthly income from the company' : 'Monthly salary'">
                    @error('monthly_salary') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field">
                    <div class="tfa-label">Salary Account Number <span class="tfa-req">*</span></div>
                    <input class="tfa-input {{ $errors->has('salary_account_number') ? 'error' : '' }}" type="text" name="salary_account_number" value="{{ old('salary_account_number') }}" placeholder="Salary account number">
                    @error('salary_account_number') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field">
                    <div class="tfa-label">Bank Name <span class="tfa-req">*</span></div>
                    <input class="tfa-input {{ $errors->has('bank_name') ? 'error' : '' }}" type="text" name="bank_name" value="{{ old('bank_name') }}" placeholder="Bank name">
                    @error('bank_name') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field">
                    <div class="tfa-label">Social Media Platform</div>
                    <select class="tfa-input {{ $errors->has('social_media_platform') ? 'error' : '' }}" name="social_media_platform">
                        <option value="">Select platform</option>
                        <option value="facebook" @selected(old('social_media_platform') === 'facebook')>Facebook</option>
                        <option value="instagram" @selected(old('social_media_platform') === 'instagram')>Instagram</option>
                        <option value="x" @selected(old('social_media_platform') === 'x')>X</option>
                    </select>
                    @error('social_media_platform') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field">
                    <div class="tfa-label">Social Media Handle</div>
                    <input class="tfa-input {{ $errors->has('social_media_handle') ? 'error' : '' }}" type="text" name="social_media_handle" value="{{ old('social_media_handle') }}" placeholder="@handle">
                    @error('social_media_handle') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field">
                    <div class="tfa-label">Loan Amount <span class="tfa-req">*</span></div>
                    <input class="tfa-input" type="text" name="loan_amount"
                           value="{{ $fmt($loanAmount) }}" readonly>
                    <div class="tfa-hint">Pre-filled from your payment plan</div>
                </div>
            </div>
        </div>

        <div class="tfa-card" x-show="step === 2" x-cloak>
            <div class="tfa-section-title">Next of Kin</div>
            <div class="tfa-grid">
                @foreach([
                    ['next_of_kin_surname', 'Surname', 'text', true], ['next_of_kin_first_name', 'First Name', 'text', true], ['next_of_kin_other_names', 'Other Names', 'text', false], ['next_of_kin_relationship', 'Relationship', 'text', true],
                    ['next_of_kin_title', 'Title', 'text', true], ['next_of_kin_phone_primary', 'Phone Number 1', 'text', true], ['next_of_kin_phone_secondary', 'Phone Number 2', 'text', false], ['next_of_kin_email', 'Email Address', 'email', true],
                ] as [$name, $label, $type, $required])
                    <div class="tfa-field"><div class="tfa-label">{{ $label }} @if($required)<span class="tfa-req">*</span>@endif</div><input class="tfa-input {{ $errors->has($name) ? 'error' : '' }}" type="{{ $type }}" name="{{ $name }}" value="{{ old($name) }}">@error($name) <span class="tfa-error">{{ $message }}</span> @enderror</div>
                @endforeach
                <div class="tfa-field"><div class="tfa-label">Date of Birth <span class="tfa-req">*</span></div><input class="tfa-input {{ $errors->has('next_of_kin_date_of_birth') ? 'error' : '' }}" type="date" name="next_of_kin_date_of_birth" value="{{ old('next_of_kin_date_of_birth') }}">@error('next_of_kin_date_of_birth') <span class="tfa-error">{{ $message }}</span> @enderror</div>
                <div class="tfa-field"><div class="tfa-label">Gender <span class="tfa-req">*</span></div><select class="tfa-input {{ $errors->has('next_of_kin_gender') ? 'error' : '' }}" name="next_of_kin_gender"><option value="">Select gender</option><option value="female" @selected(old('next_of_kin_gender') === 'female')>Female</option><option value="male" @selected(old('next_of_kin_gender') === 'male')>Male</option></select>@error('next_of_kin_gender') <span class="tfa-error">{{ $message }}</span> @enderror</div>
                <div class="tfa-field tfa-full"><div class="tfa-label">Residential Address <span class="tfa-req">*</span></div><input class="tfa-input {{ $errors->has('next_of_kin_address') ? 'error' : '' }}" type="text" name="next_of_kin_address" value="{{ old('next_of_kin_address') }}" placeholder="Start typing and select next of kin address" autocomplete="off" data-google-address data-place-target="next_of_kin_address_place_id"><input type="hidden" name="next_of_kin_address_place_id" id="next_of_kin_address_place_id" value="{{ old('next_of_kin_address_place_id') }}">@error('next_of_kin_address') <span class="tfa-error">{{ $message }}</span> @enderror</div>
            </div>
        </div>

        {{-- Section 3: Document Uploads --}}
        <div class="tfa-card" x-show="step === 3" x-cloak>
            <div class="tfa-section-title">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M9 15h6"/><path d="M9 18h4"/></svg>
                Required Documents
            </div>

            <div class="tfa-notice info">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>Accepted formats: JPG, PNG, PDF. Max 5MB per file. Ensure documents are clear and legible before uploading.</span>
            </div>

            <div class="tfa-doc-grid">

                @php
                $docs = [
                    ['name' => 'valid_id',              'label' => 'Valid Government-Issued ID', 'required' => true,  'hint' => "National ID, Voter's Card, Driver's License or International Passport", 'icon' => 'id'],
                    ['name' => 'passport_photo',         'label' => 'Recent Passport Photograph', 'required' => true,  'hint' => 'Clear, white background, taken within the last 6 months', 'icon' => 'user'],
                    ['name' => 'work_id_card',           'label' => 'Work ID Card',              'required' => true,  'hint' => 'Current employer ID card - both sides if required', 'icon' => 'briefcase'],
                    ['name' => 'employment_letter',      'label' => 'Employment Letter / Details','required' => true,  'hint' => 'Official letter from your employer confirming employment', 'icon' => 'file'],
                    ['name' => 'bank_statements',        'label' => '6-Month Bank Statement',    'required' => true,  'hint' => 'Last 6 months of your salary account statement', 'icon' => 'bank'],
                ];
                @endphp

                @foreach($docs as $doc)
                <div class="tfa-field tfa-doc-field {{ in_array($doc['name'], ['bank_statements', 'employment_letter']) ? 'tfa-doc-wide' : '' }}" x-show="applicantType === 'individual'">
                    <div class="tfa-label">{{ $doc['label'] }} @if($doc['required'])<span class="tfa-req">*</span>@endif</div>
                    <label class="tfa-file-wrap {{ $errors->has($doc['name']) ? 'border-red-400' : '' }}" for="{{ $doc['name'] }}">
                        <span class="tfa-file-label">
                            <span class="tfa-file-icon">
                                @switch($doc['icon'])
                                    @case('id')
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M15 9h3"/><path d="M15 13h3"/><path d="M7 16h5"/></svg>
                                        @break
                                    @case('user')
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                                        @break
                                    @case('briefcase')
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M3 12h18"/></svg>
                                        @break
                                    @case('bank')
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 10 9-6 9 6"/><path d="M5 10v9"/><path d="M19 10v9"/><path d="M3 19h18"/></svg>
                                        @break
                                    @default
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/></svg>
                                @endswitch
                            </span>
                            <span>Click to upload <strong>{{ $doc['label'] }}</strong></span>
                            <span style="font-size:11px;color:var(--gray-400)">{{ $doc['hint'] }}</span>
                        </span>
                        <input type="file" id="{{ $doc['name'] }}" name="{{ $doc['name'] }}"
                               accept=".jpg,.jpeg,.png,.pdf"
                               onchange="showFileName(this, '{{ $doc['name'] }}_name')">
                        <div class="tfa-file-name" id="{{ $doc['name'] }}_name"></div>
                    </label>
                    @error($doc['name']) <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                @endforeach

                @php
                $companyDocs = [
                    ['name' => 'representative_valid_id', 'label' => 'Representative Valid ID', 'hint' => 'Government-issued ID for the authorized representative', 'required' => true],
                    ['name' => 'cac_status_report', 'label' => 'Status Report (Form CAC 1.1)', 'hint' => 'Current CAC status report', 'required' => true],
                    ['name' => 'share_certificate', 'label' => 'Share Certificate', 'hint' => 'Company share certificate', 'required' => true],
                    ['name' => 'memart', 'label' => 'Memorandum and Articles of Association (MEMART)', 'hint' => 'Company MEMART document', 'required' => true],
                    ['name' => 'register_of_members', 'label' => 'Register of Members', 'hint' => 'Current register of members', 'required' => true],
                    ['name' => 'shareholders_agreement', 'label' => "Shareholders' Agreement", 'hint' => 'Executed shareholder agreement', 'required' => true],
                    ['name' => 'return_of_allotment', 'label' => 'Return of Allotment of Shares (Form CAC 2)', 'hint' => 'CAC Form 2 return of allotment', 'required' => true],
                    ['name' => 'certificate_of_incorporation', 'label' => 'Certificate of Incorporation', 'hint' => 'Recommended company registration certificate', 'required' => false],
                    ['name' => 'board_resolution', 'label' => 'Board Resolution / Authorization Letter', 'hint' => 'Recommended authorization for the representative', 'required' => false],
                    ['name' => 'company_bank_statement', 'label' => 'Company Bank Statement', 'hint' => 'Recommended recent company bank statement', 'required' => false],
                    ['name' => 'tin_certificate', 'label' => 'Tax Identification Number / TIN Certificate', 'hint' => 'Recommended tax registration certificate', 'required' => false],
                ];
                @endphp

                @foreach($companyDocs as $doc)
                <div class="tfa-field tfa-doc-field tfa-doc-wide" x-show="applicantType === 'company'">
                    <div class="tfa-label">{{ $doc['label'] }} @if($doc['required'])<span class="tfa-req">*</span>@else <span class="tfa-hint">Recommended</span>@endif</div>
                    <label class="tfa-file-wrap {{ $errors->has($doc['name']) ? 'border-red-400' : '' }}" for="{{ $doc['name'] }}">
                        <span class="tfa-file-label">
                            <span class="tfa-file-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/></svg>
                            </span>
                            <span>Click to upload <strong>{{ $doc['label'] }}</strong></span>
                            <span style="font-size:11px;color:var(--gray-400)">{{ $doc['hint'] }}</span>
                        </span>
                        <input type="file" id="{{ $doc['name'] }}" name="{{ $doc['name'] }}" accept=".jpg,.jpeg,.png,.pdf" onchange="showFileName(this, '{{ $doc['name'] }}_name')">
                        <div class="tfa-file-name" id="{{ $doc['name'] }}_name"></div>
                    </label>
                    @error($doc['name']) <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                @endforeach

            </div>
        </div>

        {{-- Hidden plan fields --}}
        <div class="tfa-card" style="display:none;" aria-hidden="true">
            <div class="tfa-section-title">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M7 15h2"/><path d="M12 15h5"/></svg>
                Down Payment Method
            </div>
            <div class="tfa-notice info">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>Choose how you want to pay the down payment.</span>
            </div>
            <div class="tfa-pay-grid">
                <label class="tfa-pay-card">
                    <input type="radio" value="gateway" disabled>
                    <span class="tfa-pay-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M7 15h2"/><path d="M12 15h5"/></svg>
                    </span>
                    <span>
                        <span class="tfa-pay-title">Pay online now</span>
                        <span class="tfa-pay-copy">Use card, bank transfer, or USSD through the secure payment gateway. Best for instant confirmation.</span>
                    </span>
                    <span class="tfa-pay-radio"></span>
                </label>
                <label class="tfa-pay-card">
                    <input type="radio" value="bank_transfer" disabled>
                    <span class="tfa-pay-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 10 9-6 9 6"/><path d="M5 10v9"/><path d="M19 10v9"/><path d="M3 19h18"/></svg>
                    </span>
                    <span>
                        <span class="tfa-pay-title">Bank transfer</span>
                        <span class="tfa-pay-copy">Submit the application first, then view account details and submit your transfer reference for verification.</span>
                    </span>
                    <span class="tfa-pay-radio"></span>
                </label>
            </div>
        </div>

        <div class="tfa-card" x-show="step === 4" x-cloak>
            <div class="tfa-section-title">
                Fast Credit Agreement & Declaration
            </div>
            @include('livewire.pages.flight.partials.fastcredit-agreement', ['class' => 'tfa-agreement-box'])
            <div class="tfa-grid">
                <div class="tfa-field tfa-full">
                    <label class="tf-agree-row" style="margin:0;">
                        <input type="checkbox" name="fast_credit_agreement" value="1" @checked(old('fast_credit_agreement'))>
                        <span>I confirm that I have read, understood and agreed to the above terms and conditions. I also authorize my employer to deduct monthly instalments as per the agreement shown over leaf from my salary until the loan has been fully paid and to recover any outstanding instalments against my terminal dues in the event of termination of employment before the loan is fully recovered.</span>
                    </label>
                    @error('fast_credit_agreement') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field tfa-full">
                    <div class="tfa-label">Signer Name <span class="tfa-req">*</span></div>
                    <input class="tfa-input {{ $errors->has('digital_signature') ? 'error' : '' }}" type="text" name="digital_signature" value="{{ old('digital_signature', $prefillName) }}" placeholder="Full name of the person signing">
                    @error('digital_signature') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field tfa-full">
                    <div class="tfa-label">Draw Signature <span class="tfa-req">*</span></div>
                    <div class="tfa-signature-box {{ $errors->has('digital_signature_image') ? 'error' : '' }}">
                        <div class="tfa-signature-toolbar">
                            <span>Use your mouse, trackpad, or finger to sign inside the box.</span>
                            <button type="button" class="tfa-signature-clear" id="tfa-signature-clear">Clear</button>
                        </div>
                        <canvas id="tfa-signature-pad" class="tfa-signature-canvas"></canvas>
                    </div>
                    <input type="hidden" name="digital_signature_image" id="tfa-signature-image" value="{{ old('digital_signature_image') }}">
                    <div class="tfa-hint">Your signature, signer name, timestamp, IP address, and device details will be stored with the application acceptance record.</div>
                    @error('digital_signature_image') <span class="tfa-error">{{ $message }}</span> @enderror
                    <span class="tfa-error" id="tfa-signature-error" style="display:none;">Please draw your signature before submitting.</span>
                </div>
                <div class="tfa-field tfa-full" style="margin-top:10px;padding-top:18px;border-top:1px solid var(--gray-200);">
                    <div class="tfa-label">Witness Full Name <span class="tfa-req">*</span></div>
                    <input class="tfa-input {{ $errors->has('witness_full_name') ? 'error' : '' }}" type="text" name="witness_full_name" value="{{ old('witness_full_name') }}" placeholder="Full legal name of the witness">
                    @error('witness_full_name') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field tfa-full">
                    <div class="tfa-label">Witness Signature <span class="tfa-req">*</span></div>
                    <div class="tfa-signature-box {{ $errors->has('witness_signature_image') ? 'error' : '' }}">
                        <div class="tfa-signature-toolbar">
                            <span>The witness must draw their own signature inside this box.</span>
                            <button type="button" class="tfa-signature-clear" id="tfa-witness-signature-clear">Clear</button>
                        </div>
                        <canvas id="tfa-witness-signature-pad" class="tfa-signature-canvas"></canvas>
                    </div>
                    <input type="hidden" name="witness_signature_image" id="tfa-witness-signature-image" value="{{ old('witness_signature_image') }}">
                    @error('witness_signature_image') <span class="tfa-error">{{ $message }}</span> @enderror
                    <span class="tfa-error" id="tfa-witness-signature-error" style="display:none;">The witness must draw their signature before submission.</span>
                </div>
                <div class="tfa-field tfa-full">
                    <label class="tf-agree-row" style="margin:0;">
                        <input type="checkbox" name="witness_declaration" value="1" @checked(old('witness_declaration'))>
                        <span>I confirm that I am the named witness and that the signature above is my own.</span>
                    </label>
                    @error('witness_declaration') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="tfa-btn-row">
            <a href="{{ route('flights.travelflex') }}" class="tfa-btn-ghost" x-show="step === 1">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Back
            </a>
            <button type="button" class="tfa-btn-ghost" x-show="step > 1" @click="step = Math.max(1, step - 1)">
                Previous section
            </button>
            <button type="button" class="tfa-btn-primary" x-show="step < 4" @click="step = Math.min(4, step + 1)">
                Continue
            </button>
            <button type="submit" class="tfa-btn-primary" id="tfa-submit" x-show="step === 4" x-cloak>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                Submit for Fast Credit Review
            </button>
        </div>
    </form>
</div>

<script>
    function showFileName(input, nameId) {
        const el = document.getElementById(nameId);
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const sizeMb = file.size ? (file.size / (1024 * 1024)).toFixed(1) + 'MB' : '';
            el.textContent = sizeMb ? file.name + ' · ' + sizeMb : file.name;
            el.classList.add('visible');
            input.closest('.tfa-file-wrap')?.classList.add('has-file');
        } else {
            el.textContent = '';
            el.classList.remove('visible');
            input.closest('.tfa-file-wrap')?.classList.remove('has-file');
        }
    }
    const signatureCanvas = document.getElementById('tfa-signature-pad');
    const signatureInput = document.getElementById('tfa-signature-image');
    const signatureError = document.getElementById('tfa-signature-error');
    let signatureHasInk = Boolean(signatureInput?.value);
    let signatureDrawing = false;
    let signatureContext = null;
    let signatureRatio = 1;
    let signatureCssWidth = 0;
    let signatureCssHeight = 0;
    const witnessSignatureCanvas = document.getElementById('tfa-witness-signature-pad');
    const witnessSignatureInput = document.getElementById('tfa-witness-signature-image');
    const witnessSignatureError = document.getElementById('tfa-witness-signature-error');
    let witnessSignatureHasInk = Boolean(witnessSignatureInput?.value);
    let witnessSignatureDrawing = false;
    let witnessSignatureContext = null;
    let witnessSignatureRatio = 1;
    let witnessSignatureCssWidth = 0;
    let witnessSignatureCssHeight = 0;

    function configureSignatureContext() {
        if (!signatureContext) return;
        signatureContext.setTransform(signatureRatio, 0, 0, signatureRatio, 0, 0);
        signatureContext.lineWidth = 2.4;
        signatureContext.lineCap = 'round';
        signatureContext.lineJoin = 'round';
        signatureContext.strokeStyle = '#101828';
    }
    window.initTravelFlexAddressAutocomplete = function() {
        if (!window.google?.maps?.places) return;

        document.querySelectorAll('[data-google-address]').forEach(function(input) {
            if (input.dataset.googleReady === '1') return;
            input.dataset.googleReady = '1';

            const autocomplete = new google.maps.places.Autocomplete(input, {
                fields: ['formatted_address', 'place_id'],
                types: ['address'],
                componentRestrictions: { country: 'ng' },
            });
            let selectedValue = input.value;

            autocomplete.addListener('place_changed', function() {
                const place = autocomplete.getPlace();
                if (place.formatted_address) {
                    input.value = place.formatted_address;
                    selectedValue = place.formatted_address;
                }

                const target = document.getElementById(input.dataset.placeTarget);
                if (target) {
                    target.value = place.place_id || '';
                }
            });

            input.addEventListener('input', function() {
                if (input.value === selectedValue) return;
                const target = document.getElementById(input.dataset.placeTarget);
                if (target) {
                    target.value = '';
                }
            });
        });
    };

    document.addEventListener('DOMContentLoaded', function() {
        window.initTravelFlexAddressAutocomplete();
    });

    function resizeSignatureCanvas(force = false) {
        if (!signatureCanvas) return false;
        const previous = signatureInput?.value || '';
        const rect = signatureCanvas.getBoundingClientRect();

        if (rect.width < 20 || rect.height < 20) {
            return false;
        }

        signatureRatio = Math.max(window.devicePixelRatio || 1, 1);
        signatureCssWidth = rect.width;
        signatureCssHeight = rect.height;

        const nextWidth = Math.floor(rect.width * signatureRatio);
        const nextHeight = Math.floor(rect.height * signatureRatio);
        if (!force && signatureCanvas.width === nextWidth && signatureCanvas.height === nextHeight && signatureContext) {
            return true;
        }

        signatureCanvas.width = nextWidth;
        signatureCanvas.height = nextHeight;
        signatureContext = signatureCanvas.getContext('2d');
        configureSignatureContext();

        if (previous) {
            const image = new Image();
            image.onload = function() {
                signatureContext.drawImage(image, 0, 0, signatureCssWidth, signatureCssHeight);
            };
            image.src = previous;
        }

        return true;
    }

    function signaturePoint(event) {
        const rect = signatureCanvas.getBoundingClientRect();
        return { x: event.clientX - rect.left, y: event.clientY - rect.top };
    }

    function startSignature(event) {
        if (!resizeSignatureCanvas()) return;
        event.preventDefault();
        signatureCanvas.setPointerCapture?.(event.pointerId);
        signatureDrawing = true;
        signatureHasInk = true;
        const point = signaturePoint(event);
        signatureContext.beginPath();
        signatureContext.moveTo(point.x, point.y);
        signatureContext.lineTo(point.x + 0.01, point.y + 0.01);
        signatureContext.stroke();
        signatureError.style.display = 'none';
    }

    function moveSignature(event) {
        if (!signatureDrawing || !signatureContext) return;
        event.preventDefault();
        const point = signaturePoint(event);
        signatureContext.lineTo(point.x, point.y);
        signatureContext.stroke();
    }

    function finishSignature(event) {
        if (!signatureDrawing) return;
        event?.preventDefault?.();
        signatureDrawing = false;
        if (!signatureHasInk) return;
        signatureInput.value = signatureCanvas.toDataURL('image/png');
    }

    function clearSignature() {
        if (!resizeSignatureCanvas(true) || !signatureContext || !signatureCanvas) return;
        signatureContext.save();
        signatureContext.setTransform(1, 0, 0, 1, 0, 0);
        signatureContext.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
        signatureContext.restore();
        configureSignatureContext();
        signatureHasInk = false;
        signatureInput.value = '';
    }

    function configureWitnessSignatureContext() {
        if (!witnessSignatureContext) return;
        witnessSignatureContext.setTransform(witnessSignatureRatio, 0, 0, witnessSignatureRatio, 0, 0);
        witnessSignatureContext.lineWidth = 2.4;
        witnessSignatureContext.lineCap = 'round';
        witnessSignatureContext.lineJoin = 'round';
        witnessSignatureContext.strokeStyle = '#101828';
    }

    function resizeWitnessSignatureCanvas(force = false) {
        if (!witnessSignatureCanvas) return false;
        const previous = witnessSignatureInput?.value || '';
        const rect = witnessSignatureCanvas.getBoundingClientRect();
        if (rect.width < 20 || rect.height < 20) return false;

        witnessSignatureRatio = Math.max(window.devicePixelRatio || 1, 1);
        witnessSignatureCssWidth = rect.width;
        witnessSignatureCssHeight = rect.height;
        const nextWidth = Math.floor(rect.width * witnessSignatureRatio);
        const nextHeight = Math.floor(rect.height * witnessSignatureRatio);
        if (!force && witnessSignatureCanvas.width === nextWidth && witnessSignatureCanvas.height === nextHeight && witnessSignatureContext) return true;

        witnessSignatureCanvas.width = nextWidth;
        witnessSignatureCanvas.height = nextHeight;
        witnessSignatureContext = witnessSignatureCanvas.getContext('2d');
        configureWitnessSignatureContext();

        if (previous) {
            const image = new Image();
            image.onload = function() {
                witnessSignatureContext.drawImage(image, 0, 0, witnessSignatureCssWidth, witnessSignatureCssHeight);
            };
            image.src = previous;
        }
        return true;
    }

    function witnessSignaturePoint(event) {
        const rect = witnessSignatureCanvas.getBoundingClientRect();
        return { x: event.clientX - rect.left, y: event.clientY - rect.top };
    }

    function startWitnessSignature(event) {
        if (!resizeWitnessSignatureCanvas()) return;
        event.preventDefault();
        witnessSignatureCanvas.setPointerCapture?.(event.pointerId);
        witnessSignatureDrawing = true;
        witnessSignatureHasInk = true;
        const point = witnessSignaturePoint(event);
        witnessSignatureContext.beginPath();
        witnessSignatureContext.moveTo(point.x, point.y);
        witnessSignatureContext.lineTo(point.x + 0.01, point.y + 0.01);
        witnessSignatureContext.stroke();
        witnessSignatureError.style.display = 'none';
    }

    function moveWitnessSignature(event) {
        if (!witnessSignatureDrawing || !witnessSignatureContext) return;
        event.preventDefault();
        const point = witnessSignaturePoint(event);
        witnessSignatureContext.lineTo(point.x, point.y);
        witnessSignatureContext.stroke();
    }

    function finishWitnessSignature(event) {
        if (!witnessSignatureDrawing) return;
        event?.preventDefault?.();
        witnessSignatureDrawing = false;
        if (witnessSignatureHasInk) witnessSignatureInput.value = witnessSignatureCanvas.toDataURL('image/png');
    }

    function clearWitnessSignature() {
        if (!resizeWitnessSignatureCanvas(true) || !witnessSignatureContext || !witnessSignatureCanvas) return;
        witnessSignatureContext.save();
        witnessSignatureContext.setTransform(1, 0, 0, 1, 0, 0);
        witnessSignatureContext.clearRect(0, 0, witnessSignatureCanvas.width, witnessSignatureCanvas.height);
        witnessSignatureContext.restore();
        configureWitnessSignatureContext();
        witnessSignatureHasInk = false;
        witnessSignatureInput.value = '';
    }

    if (signatureCanvas) {
        window.addEventListener('resize', () => resizeSignatureCanvas(true));
        signatureCanvas.addEventListener('pointerdown', startSignature);
        signatureCanvas.addEventListener('pointermove', moveSignature);
        signatureCanvas.addEventListener('pointerup', finishSignature);
        signatureCanvas.addEventListener('pointercancel', finishSignature);
        signatureCanvas.addEventListener('pointerleave', finishSignature);
        document.getElementById('tfa-signature-clear')?.addEventListener('click', clearSignature);
    }

    if (witnessSignatureCanvas) {
        window.addEventListener('resize', () => resizeWitnessSignatureCanvas(true));
        witnessSignatureCanvas.addEventListener('pointerdown', startWitnessSignature);
        witnessSignatureCanvas.addEventListener('pointermove', moveWitnessSignature);
        witnessSignatureCanvas.addEventListener('pointerup', finishWitnessSignature);
        witnessSignatureCanvas.addEventListener('pointercancel', finishWitnessSignature);
        witnessSignatureCanvas.addEventListener('pointerleave', finishWitnessSignature);
        document.getElementById('tfa-witness-signature-clear')?.addEventListener('click', clearWitnessSignature);
    }

    document.getElementById('tfa-form')?.addEventListener('submit', function(event) {
        if (signatureCanvas && (!signatureHasInk || !signatureInput.value)) {
            event.preventDefault();
            signatureError.style.display = 'block';
            signatureCanvas.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        if (witnessSignatureCanvas && (!witnessSignatureHasInk || !witnessSignatureInput.value)) {
            event.preventDefault();
            witnessSignatureError.style.display = 'block';
            witnessSignatureCanvas.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        const btn = document.getElementById('tfa-submit');
        if (!btn) return;
        btn.disabled = true;
        btn.textContent = 'Submitting application...';
    });
</script>
@if(config('services.google_maps.key'))
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=places&callback=initTravelFlexAddressAutocomplete" async defer></script>
@endif
@endcomponent
