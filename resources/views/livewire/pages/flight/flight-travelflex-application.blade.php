{{-- resources/views/livewire/pages/flight/flight-travelflex-application.blade.php --}}
@component('layouts.app', ['title' => 'TravelFlex — Loan Application'])

@php
    $contact    = session('bookingContact', []);
    $passengers = session('bookingPassengers', []);
    $primary    = collect($passengers)->firstWhere('is_primary', true) ?? ($passengers[0] ?? []);
    $prefillName  = trim(($primary['first_name'] ?? '') . ' ' . ($primary['last_name'] ?? ''));
    $prefillEmail = $contact['email'] ?? '';

    $tfPlan       = session('travelFlexPlan', []);
    $loanAmount   = (float) ($tfPlan['grand_total'] ?? 0);
    $downPayment  = (float) ($tfPlan['down_payment'] ?? 0);
    $repayPlan    = $tfPlan['repayment_plan'] ?? '';

    $flight       = session('bookingFlight.flight') ?? session('bookingFlight', []);
    $segments     = $flight['segments'] ?? [];
    $firstSeg     = $segments[0] ?? [];
    $lastSeg      = !empty($segments) ? $segments[count($segments)-1] : [];
    $currency     = $flight['currency'] ?? 'NGN';
    $sym          = match($currency) { 'NGN' => '₦', 'USD' => '$', 'GBP' => '£', 'EUR' => '€', default => $currency.' ' };
    $fmt          = fn($v) => $sym . number_format((float)$v, 2);
    $errors       = $errors ?? new \Illuminate\Support\MessageBag();
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
    .tfa-file-wrap input[type=file] { display: none; }
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

<div class="tfa-wrap">

    {{-- Hero --}}
    <div class="tfa-hero">
        <div class="tfa-hero-badge">📋 TravelFlex Loan Application</div>
        <div class="tfa-hero-title">Complete Your Loan Application</div>
        <div class="tfa-hero-sub">Please fill in all required fields accurately. Your application will be reviewed by our lending partner. Required documents must be uploaded to process your application.</div>
    </div>

    {{-- Loan Summary Strip --}}
    <div class="tfa-loan-strip">
        <div class="tfa-loan-item">
            <div class="tfa-loan-lbl">Flight</div>
            <div class="tfa-loan-val">{{ ($firstSeg['from']??'') }} → {{ ($lastSeg['to']??'') }}</div>
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
            <div class="tfa-section-title">👤 Personal Information</div>
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
            <div class="tfa-section-title">💼 Employment Information</div>
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
            <div class="tfa-section-title">📎 Required Documents</div>

            <div class="tfa-notice info">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>Accepted formats: JPG, PNG, PDF. Max 5MB per file. Ensure documents are clear and legible before uploading.</span>
            </div>

            <div class="tfa-grid">

                @php
                $docs = [
                    ['name' => 'valid_id',              'label' => 'Valid Government-Issued ID', 'required' => true,  'hint' => 'National ID, Voter's Card, Driver's License or International Passport', 'icon' => '🪪'],
                    ['name' => 'passport_photo',         'label' => 'Recent Passport Photograph', 'required' => true,  'hint' => 'Clear, white background, taken within the last 6 months', 'icon' => '🤳'],
                    ['name' => 'work_id_card',           'label' => 'Work ID Card',              'required' => true,  'hint' => 'Current employer ID card — both sides if required', 'icon' => '🏢'],
                    ['name' => 'employment_letter',      'label' => 'Employment Letter / Details','required' => true,  'hint' => 'Official letter from your employer confirming employment', 'icon' => '📄'],
                    ['name' => 'bank_statements',        'label' => '6-Month Bank Statement',    'required' => true,  'hint' => 'Last 6 months of your salary account statement', 'icon' => '🏦'],
                ];
                @endphp

                @foreach($docs as $doc)
                <div class="tfa-field {{ in_array($doc['name'], ['bank_statements', 'employment_letter']) ? 'tfa-full' : '' }}">
                    <div class="tfa-label">{{ $doc['label'] }} @if($doc['required'])<span class="tfa-req">*</span>@endif</div>
                    <div class="tfa-file-wrap {{ $errors->has($doc['name']) ? 'border-red-400' : '' }}"
                         onclick="document.getElementById('{{ $doc['name'] }}').click()">
                        <label class="tfa-file-label" for="{{ $doc['name'] }}">
                            <span class="tfa-file-icon">{{ $doc['icon'] }}</span>
                            <span>Click to upload <strong>{{ $doc['label'] }}</strong></span>
                            <span style="font-size:11px;color:var(--gray-400)">{{ $doc['hint'] }}</span>
                        </label>
                        <input type="file" id="{{ $doc['name'] }}" name="{{ $doc['name'] }}"
                               accept=".jpg,.jpeg,.png,.pdf"
                               onchange="showFileName(this, '{{ $doc['name'] }}_name')">
                        <div class="tfa-file-name" id="{{ $doc['name'] }}_name"></div>
                    </div>
                    @error($doc['name']) <span class="tfa-error">{{ $message }}</span> @enderror
                </div>
                @endforeach

            </div>
        </div>

        {{-- ── Hidden plan fields ── --}}
        <input type="hidden" name="down_payment"   value="{{ $tfPlan['down_payment']   ?? 0 }}">
        <input type="hidden" name="down_percent"   value="{{ $tfPlan['down_percent']   ?? 30 }}">
        <input type="hidden" name="repayment_plan" value="{{ $tfPlan['repayment_plan'] ?? '' }}">
        <input type="hidden" name="grand_total"    value="{{ $tfPlan['grand_total']    ?? 0 }}">
        <input type="hidden" name="total_interest" value="{{ $tfPlan['total_interest'] ?? 0 }}">
        <input type="hidden" name="schedule_json"  value="{{ json_encode($tfPlan['schedule'] ?? []) }}">
        <input type="hidden" name="pay_method"     value="{{ request('pay_method', 'gateway') }}">

        <div class="tfa-btn-row">
            <a href="{{ route('flights.travelflex') }}" class="tfa-btn-ghost">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Back
            </a>
            <button type="submit" class="tfa-btn-primary" id="tfa-submit">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                Submit Application &amp; Pay Down Payment
            </button>
        </div>
    </form>
</div>

<script>
    function showFileName(input, nameId) {
        const el = document.getElementById(nameId);
        if (input.files && input.files[0]) {
            el.textContent = '✓ ' + input.files[0].name;
            el.style.display = 'block';
        }
    }
    document.getElementById('tfa-form').addEventListener('submit', function() {
        const btn = document.getElementById('tfa-submit');
        btn.disabled = true;
        btn.textContent = 'Submitting application…';
    });
</script>
@endcomponent