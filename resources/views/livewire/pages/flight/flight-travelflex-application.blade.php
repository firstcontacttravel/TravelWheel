{{-- resources/views/livewire/pages/flight/flight-travelflex-application.blade.php --}}
@component('layouts.app', ['title' => 'TravelFlex - Loan Application'])

@php
    $contact    = session('bookingContact', []);
    $passengers = session('bookingPassengers', []);
    $primary    = collect($passengers)->firstWhere('is_primary', true) ?? ($passengers[0] ?? []);
    $prefillName  = trim(($primary['first_name'] ?? '') . ' ' . ($primary['last_name'] ?? ''));
    $prefillEmail = $contact['email'] ?? '';

    $tfPlan       = session('travelFlexPlan', []);
    $ticketCost   = (float) ($tfPlan['ticket_cost'] ?? 0);
    $downPayment  = (float) ($tfPlan['down_payment'] ?? 0);
    $loanAmount   = (float) ($tfPlan['loan_amount'] ?? $tfPlan['remaining_balance'] ?? max(0, $ticketCost - $downPayment));
    $repayPlan    = $tfPlan['repayment_plan'] ?? '';

    $flight       = session('bookingFlight.flight') ?? session('bookingFlight', []);
    $segments     = $flight['segments'] ?? [];
    $multiLegs    = $flight['multiLegs'] ?? [];
    $isMulti      = count($multiLegs) > 0;
    $firstSeg     = $segments[0] ?? [];
    $lastSeg      = !empty($segments) ? $segments[count($segments)-1] : [];
    $currency     = $flight['currency'] ?? 'NGN';
    $sym          = match($currency) { 'NGN' => '₦', 'USD' => '$', 'GBP' => '£', 'EUR' => '€', default => $currency.' ' };
    $fmt          = fn($v) => $sym . number_format((float)$v, 2);
    $errors       = $errors ?? new \Illuminate\Support\MessageBag();
    $routeLines   = [];
    if ($isMulti) {
        foreach ($multiLegs as $leg) {
            $routeLines[] = ($leg['from'] ?? '') . ' â†’ ' . ($leg['to'] ?? '');
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
    @media(max-width:640px) {
        body { margin-top: 0; }
        .tfa-wrap { padding: 14px 12px 64px; }
        .tfa-hero, .tfa-card { padding: 16px; }
        .tfa-doc-grid { grid-template-columns: 1fr; }
        .tfa-doc-field, .tfa-doc-field.tfa-doc-wide { grid-column: 1; }
        .tfa-file-wrap { min-height: 154px; }
        .tfa-pay-grid { grid-template-columns: 1fr; }
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
            <div class="tfa-loan-lbl">Down Payment</div>
            <div class="tfa-loan-val">{{ $fmt($downPayment) }}</div>
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
          enctype="multipart/form-data" id="tfa-form">
        @csrf

        {{-- ── Section 1: Personal Information ── --}}
        <div class="tfa-card">
            <div class="tfa-section-title">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                Personal Information
            </div>
            <div class="tfa-grid">
                <div class="tfa-field tfa-full">
                    <div class="tfa-label">Full Name <span class="tfa-req">*</span></div>
                    <input class="tfa-input {{ $errors->has('full_name') ? 'error' : '' }}"
                           type="text" name="full_name" value="{{ old('full_name', $prefillName) }}"
                           placeholder="Full legal name as on ID">
                    @error('full_name') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field tfa-full">
                    <div class="tfa-label">Home Address <span class="tfa-req">*</span></div>
                    <textarea class="tfa-textarea {{ $errors->has('home_address') ? 'error' : '' }}"
                              name="home_address" placeholder="Full residential address including city and state"
                              rows="2">{{ old('home_address') }}</textarea>
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
                    <div class="tfa-label">Bank Verification Number (BVN) <span class="tfa-req">*</span></div>
                    <input class="tfa-input {{ $errors->has('bvn') ? 'error' : '' }}"
                           type="text" name="bvn" value="{{ old('bvn') }}"
                           maxlength="11" placeholder="11-digit BVN">
                    <div class="tfa-hint">Your BVN is used for credit verification only</div>
                    @error('bvn') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        {{-- ── Section 2: Employment Information ── --}}
        <div class="tfa-card">
            <div class="tfa-section-title">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M3 12h18"/></svg>
                Employment Information
            </div>
            <div class="tfa-grid">
                <div class="tfa-field">
                    <div class="tfa-label">Employer Company Name <span class="tfa-req">*</span></div>
                    <input class="tfa-input {{ $errors->has('employer_name') ? 'error' : '' }}"
                           type="text" name="employer_name" value="{{ old('employer_name') }}"
                           placeholder="Name of your employer">
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
                    <textarea class="tfa-textarea {{ $errors->has('employer_address') ? 'error' : '' }}"
                              name="employer_address" placeholder="Full company address including city and state"
                              rows="2">{{ old('employer_address') }}</textarea>
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
                    <div class="tfa-label">Staff Number / Employee ID <span class="tfa-req">*</span></div>
                    <input class="tfa-input {{ $errors->has('staff_number') ? 'error' : '' }}"
                           type="text" name="staff_number" value="{{ old('staff_number') }}"
                           placeholder="Your company staff ID">
                    @error('staff_number') <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                <div class="tfa-field">
                    <div class="tfa-label">Loan Amount <span class="tfa-req">*</span></div>
                    <input class="tfa-input" type="text" name="loan_amount"
                           value="{{ $fmt($loanAmount) }}" readonly>
                    <div class="tfa-hint">Pre-filled from your payment plan</div>
                </div>
            </div>
        </div>

        {{-- ── Section 3: Document Uploads ── --}}
        <div class="tfa-card">
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
                <div class="tfa-field tfa-doc-field {{ in_array($doc['name'], ['bank_statements', 'employment_letter']) ? 'tfa-doc-wide' : '' }}">
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

            </div>
        </div>

        {{-- ── Hidden plan fields ── --}}
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

        <div class="tfa-btn-row">
            <a href="{{ route('flights.travelflex') }}" class="tfa-btn-ghost">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Back
            </a>
            <button type="submit" class="tfa-btn-primary" id="tfa-submit">
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
    document.getElementById('tfa-form')?.addEventListener('submit', function() {
        const btn = document.getElementById('tfa-submit');
        if (!btn) return;
        btn.disabled = true;
        btn.textContent = 'Submitting application...';
    });
</script>
@endcomponent
