@component('layouts.app', ['title' => 'TravelFlex - Bank Transfer'])

@php
    $bookingFlight = session('bookingFlight', []);
    $flight = $bookingFlight['flight'] ?? $bookingFlight;
    $tfPlan = session('travelFlexPlan', []);
    $applicant = session('travelFlexApplicant', []);
    $currency = $flight['currency'] ?? 'NGN';
    $sym = match($currency) { 'NGN' => html_entity_decode('&#8358;', ENT_QUOTES, 'UTF-8'), 'USD' => '$', 'GBP' => html_entity_decode('&pound;', ENT_QUOTES, 'UTF-8'), 'EUR' => html_entity_decode('&euro;', ENT_QUOTES, 'UTF-8'), default => $currency . ' ' };
    $fmt = fn($v) => $sym . number_format((float) $v, 2);
    $segments = $flight['segments'] ?? [];
    $firstSeg = $segments[0] ?? [];
    $lastSeg = !empty($segments) ? $segments[count($segments) - 1] : [];
    $route = trim(($firstSeg['from'] ?? '') . ' → ' . ($lastSeg['to'] ?? ''), ' →');
    $upfrontPaymentTotal = (float) ($tfPlan['upfront_payment_total'] ?? (($tfPlan['down_payment'] ?? 0) + ($tfPlan['administration_fee'] ?? 0) + ($tfPlan['insurance_fee'] ?? 0)));
    $stage = $stage ?? 'deposit';
    $feesAmount = (float) (($tfPlan['administration_fee'] ?? 0) + ($tfPlan['insurance_fee'] ?? 0));
    $depositAmount = (float) ($tfPlan['down_payment'] ?? 0);
    $stageAmount = $stage === 'fees' ? $feesAmount : $depositAmount;
    $stageLabel = $stage === 'fees' ? 'Administration & insurance fees' : 'Down payment';
@endphp

<style>
    :root {
        --tf-brand:#39328f;
        --tf-brand-700:#2f287c;
        --tf-green:#049a63;
        --tf-green-soft:#eefaf4;
        --tf-ink:#101828;
        --tf-muted:#667085;
        --tf-subtle:#98a2b3;
        --tf-line:#e6e9f0;
        --tf-soft:#f7f8fb;
    }
    body { background:var(--tf-soft); }
    .tfb-wrap { max-width:1040px; margin:0 auto; padding:28px 18px 80px; font-family:'Plus Jakarta Sans', system-ui, sans-serif; color:var(--tf-ink); }
    .tfb-hero, .tfb-card { background:#fff; border:1px solid var(--tf-line); border-radius:8px; box-shadow:0 14px 36px rgba(16,24,40,.06); }
    .tfb-hero { padding:24px; display:flex; align-items:flex-start; gap:16px; margin-bottom:18px; }
    .tfb-icon { width:52px; height:52px; border-radius:999px; display:grid; place-items:center; background:#f5f7ff; color:var(--tf-brand); flex:0 0 auto; }
    .tfb-icon svg { width:26px; height:26px; }
    .tfb-kicker { display:inline-flex; min-height:30px; align-items:center; padding:6px 10px; border-radius:999px; background:#f5f7ff; border:1px solid rgba(57,50,143,.16); color:var(--tf-brand); font-size:11px; font-weight:800; margin-bottom:10px; }
    .tfb-title { font-size:clamp(22px,2.4vw,32px); line-height:1.15; font-weight:800; letter-spacing:0; margin-bottom:8px; }
    .tfb-sub { max-width:680px; color:var(--tf-muted); font-size:14px; line-height:1.65; }
    .tfb-grid { display:grid; grid-template-columns:minmax(0,1fr) 340px; gap:18px; align-items:start; }
    .tfb-card { padding:20px; margin-bottom:16px; }
    .tfb-card-title { font-size:14px; font-weight:800; margin-bottom:12px; }
    .tfb-amount { display:flex; flex-wrap:wrap; gap:12px; }
    .tfb-amount > div { flex:1 1 160px; min-width:0; background:#fbfcfe; border:1px solid #eef1f6; border-radius:8px; padding:12px; }
    .tfb-label { font-size:10.5px; color:var(--tf-muted); text-transform:uppercase; letter-spacing:.05em; font-weight:800; margin-bottom:5px; }
    .tfb-value { font-size:16px; font-weight:800; overflow-wrap:anywhere; }
    .tfb-bank { position:relative; border:1px solid var(--tf-line); border-radius:8px; padding:14px 92px 14px 14px; margin-bottom:10px; background:#fbfcfe; min-height:82px; }
    .tfb-bank-name { font-size:11px; text-transform:uppercase; letter-spacing:.06em; color:var(--tf-muted); font-weight:800; }
    .tfb-bank-no { font-family:'DM Mono', monospace; font-size:20px; font-weight:800; margin-top:4px; }
    .tfb-bank-holder { font-size:12px; color:var(--tf-muted); margin-top:2px; }
    .tfb-copy { position:absolute; right:12px; top:50%; transform:translateY(-50%); border:1px solid var(--tf-line); background:#fff; color:var(--tf-brand); height:34px; padding:0 12px; border-radius:8px; font-weight:800; font-size:12px; cursor:pointer; }
    .tfb-form { display:grid; gap:10px; }
    .tfb-input { width:100%; height:46px; border:1px solid var(--tf-line); border-radius:8px; padding:0 12px; outline:none; font:inherit; }
    .tfb-input:focus { border-color:var(--tf-brand); box-shadow:0 0 0 4px rgba(57,50,143,.08); }
    .tfb-btn { height:50px; border:0; border-radius:8px; background:var(--tf-brand); color:#fff; font-weight:800; cursor:pointer; transition:background .18s, box-shadow .18s, transform .18s; }
    .tfb-btn:hover { background:var(--tf-brand-700); box-shadow:0 12px 26px rgba(57,50,143,.2); transform:translateY(-1px); }
    .tfb-note { display:flex; gap:10px; padding:12px 14px; border:1px solid rgba(217,119,6,.22); background:#fff8ed; color:#92400e; border-radius:8px; font-size:13px; line-height:1.55; }
    .tfb-step { display:flex; gap:10px; padding:12px 0; border-bottom:1px solid #eef1f6; }
    .tfb-step:last-child { border-bottom:0; }
    .tfb-dot { width:28px; height:28px; border-radius:999px; display:grid; place-items:center; background:#f5f7ff; color:var(--tf-brand); font-size:12px; font-weight:800; flex:0 0 auto; }
    .tfb-step-title { font-size:13px; font-weight:800; }
    .tfb-step-sub { font-size:12px; color:var(--tf-muted); line-height:1.5; margin-top:2px; }
    @media(max-width:760px) {
        body { margin-top:0 !important; }
        .tfb-wrap { padding:14px 12px 64px; }
        .tfb-hero { flex-direction:column; padding:18px; }
        .tfb-grid { grid-template-columns:1fr; }
        .tfb-bank { padding-right:14px; }
        .tfb-copy { position:static; transform:none; margin-top:12px; width:100%; }
    }
</style>

<div class="tfb-wrap">
    <section class="tfb-hero">
        <div class="tfb-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 10 9-6 9 6"/><path d="M5 10v9"/><path d="M19 10v9"/><path d="M3 19h18"/></svg>
        </div>
        <div>
            <div class="tfb-kicker">{{ $stage === 'fees' ? 'Step 2 of 2 — Administration & Insurance Fees' : 'Step 1 of 2 — TravelFlex Down Payment' }}</div>
            <div class="tfb-title">{{ $stage === 'fees' ? 'Transfer Your Administration & Insurance Fees' : 'Transfer Your TravelFlex Down Payment' }}</div>
            <div class="tfb-sub">
                @if($stage === 'fees')
                    Your down payment reference has been received. Now send the administration &amp; insurance fees to one of the accounts below, then submit your transaction reference. Your ticket is issued only after both transfers are verified.
                @else
                    Send the exact down payment amount to one of the accounts below, then submit your transaction reference. You'll be asked to transfer the administration &amp; insurance fees separately right after this step.
                @endif
            </div>
        </div>
    </section>

    <div class="tfb-grid">
        <main>
            <section class="tfb-card">
                <div class="tfb-card-title">Transfer This Amount Now</div>
                <div class="tfb-amount">
                    <div style="flex-basis:100%;"><div class="tfb-label">{{ $stageLabel }} (transfer now)</div><div class="tfb-value" style="color:var(--tf-green);font-size:24px;">{{ $fmt($stageAmount) }}</div></div>
                </div>
            </section>

            <section class="tfb-card">
                <div class="tfb-card-title">Full Payment Breakdown</div>
                <div class="tfb-amount">
                    <div><div class="tfb-label">Due after approval</div><div class="tfb-value">{{ $fmt($upfrontPaymentTotal) }}</div></div>
                    <div><div class="tfb-label">Down payment</div><div class="tfb-value">{{ $fmt($depositAmount) }} @if($stage !== 'fees')<span style="color:var(--tf-green);">(now)</span>@endif</div></div>
                    <div><div class="tfb-label">Admin + insurance</div><div class="tfb-value">{{ $fmt($feesAmount) }} @if($stage === 'fees')<span style="color:var(--tf-green);">(now)</span>@endif</div></div>
                    <div><div class="tfb-label">Remaining balance</div><div class="tfb-value">{{ $fmt($tfPlan['remaining_balance'] ?? 0) }}</div></div>
                </div>
            </section>

            <section class="tfb-card">
                <div class="tfb-card-title">Transfer To Any Account</div>
                @foreach($bankAccounts as $acct)
                    <div class="tfb-bank">
                        <div class="tfb-bank-name">{{ $acct['bank'] }}</div>
                        <div class="tfb-bank-no">{{ $acct['account_number'] }}</div>
                        <div class="tfb-bank-holder">{{ $acct['account_name'] }}</div>
                        <button type="button" class="tfb-copy" data-copy="{{ $acct['account_number'] }}">Copy</button>
                    </div>
                @endforeach
            </section>

            <section class="tfb-card">
                <div class="tfb-card-title">Submit {{ $stage === 'fees' ? 'Fees' : 'Down Payment' }} Transfer Reference</div>
                <form class="tfb-form" method="POST" action="{{ route('flights.travelflex.bank-transfer') }}">
                    @csrf
                    <input class="tfb-input" type="text" name="payment_reference" required minlength="3" maxlength="100" placeholder="Transaction ref, depositor name, or bank narration" value="{{ old('payment_reference') }}">
                    @error('payment_reference') <div style="font-size:12px;color:#dc2626;">{{ $message }}</div> @enderror
                    <button class="tfb-btn" type="submit">I Have Made This Transfer</button>
                    @if($stage !== 'fees' && $feesAmount > 0)
                        <div style="font-size:12px;color:var(--tf-muted);text-align:center;">Next: you'll be asked to transfer the {{ $fmt($feesAmount) }} administration &amp; insurance fees separately.</div>
                    @endif
                </form>
            </section>
        </main>

        <aside>
            <section class="tfb-card">
                <div class="tfb-card-title">TravelFlex Timeline</div>
                @foreach([
                    ['1', 'Application submitted', 'Your personal, employment, and document details have been saved.'],
                    ['2', 'Payment verification', 'Our team confirms the down payment against your transfer reference.'],
                    ['3', 'Provider review', 'Your application package is sent for TravelFlex provider review.'],
                    ['4', 'Ticketing', 'After approval, your ticket is issued or escalated for manual ticketing.'],
                ] as [$num, $title, $sub])
                    <div class="tfb-step">
                        <div class="tfb-dot">{{ $num }}</div>
                        <div><div class="tfb-step-title">{{ $title }}</div><div class="tfb-step-sub">{{ $sub }}</div></div>
                    </div>
                @endforeach
            </section>

            <section class="tfb-card">
                <div class="tfb-card-title">Booking Summary</div>
                <div class="tfb-amount">
                    <div><div class="tfb-label">Route</div><div class="tfb-value">{{ $route ?: '-' }}</div></div>
                    <div><div class="tfb-label">Applicant</div><div class="tfb-value">{{ $applicant['full_name'] ?? '-' }}</div></div>
                    <div><div class="tfb-label">Plan</div><div class="tfb-value">{{ $tfPlan['repayment_plan'] ?? '-' }}</div></div>
                </div>
            </section>

            <div class="tfb-note">
                <span>Use the exact amount shown. Transfers with incomplete narration may take longer to verify.</span>
            </div>
        </aside>
    </div>
</div>

<script>
document.querySelectorAll('[data-copy]').forEach((button) => {
    button.addEventListener('click', async () => {
        await navigator.clipboard.writeText(button.dataset.copy);
        button.textContent = 'Copied';
        setTimeout(() => button.textContent = 'Copy', 1400);
    });
});
</script>
@endcomponent
