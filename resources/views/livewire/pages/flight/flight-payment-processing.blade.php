@component('layouts.app', ['title' => 'Confirming Your Flight'])

@php
    $state = $paymentStatus['state'] ?? 'verifying';
    $isPaymentConfirmed = in_array($state, ['ticketing', 'complete', 'attention'], true);
    $isComplete = $state === 'complete';
@endphp

<style>
    :root {
        --ps-navy: #0a1940;
        --ps-blue: #303191;
        --ps-green: #059669;
        --ps-amber: #d97706;
        --ps-red: #dc2626;
        --ps-muted: #64748b;
        --ps-line: #e2e8f0;
        --ps-soft: #f8fafc;
    }

    body { background: var(--ps-soft); }
    .ps-shell { min-height: 70vh; display: grid; place-items: center; padding: 150px 16px 72px; }
    .ps-card { width: min(680px, 100%); background: #fff; border: 1px solid var(--ps-line); border-radius: 24px; padding: 40px; box-shadow: 0 24px 70px rgba(15, 23, 42, .10); text-align: center; }
    .ps-mark { width: 76px; height: 76px; margin: 0 auto 22px; border-radius: 50%; display: grid; place-items: center; background: #eef2ff; color: var(--ps-blue); position: relative; }
    .ps-spinner { width: 36px; height: 36px; border: 4px solid #c7d2fe; border-top-color: var(--ps-blue); border-radius: 50%; animation: ps-spin .9s linear infinite; }
    .ps-mark.complete { color: var(--ps-green); background: #ecfdf5; }
    .ps-mark.attention { color: var(--ps-amber); background: #fffbeb; }
    .ps-mark.failed { color: var(--ps-red); background: #fef2f2; }
    .ps-icon { width: 36px; height: 36px; display: none; }
    .ps-mark.complete .ps-spinner, .ps-mark.attention .ps-spinner, .ps-mark.failed .ps-spinner { display: none; }
    .ps-mark.complete .ps-icon-check, .ps-mark.attention .ps-icon-alert, .ps-mark.failed .ps-icon-alert { display: block; }
    .ps-kicker { color: var(--ps-blue); font-size: 12px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; margin-bottom: 8px; }
    .ps-title { color: var(--ps-navy); font-size: clamp(25px, 4vw, 34px); line-height: 1.2; font-weight: 800; margin: 0; }
    .ps-copy { color: var(--ps-muted); font-size: 15px; line-height: 1.75; max-width: 520px; margin: 14px auto 0; }
    .ps-reference { display: inline-flex; gap: 8px; align-items: center; margin-top: 20px; padding: 9px 14px; border-radius: 999px; background: #f1f5f9; color: #334155; font-size: 13px; }
    .ps-reference strong { color: var(--ps-navy); }
    .ps-steps { display: grid; grid-template-columns: repeat(3, 1fr); margin: 34px 0 28px; position: relative; }
    .ps-steps::before { content: ""; position: absolute; left: 16.6%; right: 16.6%; top: 15px; height: 2px; background: var(--ps-line); }
    .ps-step { position: relative; z-index: 1; color: #94a3b8; font-size: 12px; font-weight: 700; }
    .ps-step-dot { width: 32px; height: 32px; border-radius: 50%; margin: 0 auto 8px; display: grid; place-items: center; background: #fff; border: 2px solid var(--ps-line); }
    .ps-step.done { color: var(--ps-green); }
    .ps-step.done .ps-step-dot { color: #fff; background: var(--ps-green); border-color: var(--ps-green); }
    .ps-step.active { color: var(--ps-blue); }
    .ps-step.active .ps-step-dot { color: #fff; background: var(--ps-blue); border-color: var(--ps-blue); box-shadow: 0 0 0 6px #eef2ff; }
    .ps-note { padding: 14px 16px; border: 1px solid #bfdbfe; border-radius: 12px; background: #eff6ff; color: #1e40af; font-size: 13px; line-height: 1.6; }
    .ps-actions { display: none; justify-content: center; gap: 10px; flex-wrap: wrap; margin-top: 22px; }
    .ps-actions.visible { display: flex; }
    .ps-btn { appearance: none; border: 0; border-radius: 10px; padding: 11px 16px; font: inherit; font-weight: 800; cursor: pointer; text-decoration: none; }
    .ps-btn-primary { color: #fff; background: var(--ps-blue); }
    .ps-btn-secondary { color: var(--ps-navy); background: #f1f5f9; }
    @keyframes ps-spin { to { transform: rotate(360deg); } }
    @media (max-width: 600px) {
        .ps-shell { padding: 120px 12px 48px; }
        .ps-card { padding: 30px 18px; border-radius: 18px; }
        .ps-step { font-size: 10px; }
    }
    @media (prefers-reduced-motion: reduce) { .ps-spinner { animation-duration: 1.8s; } }
</style>

<main class="ps-shell">
    <section class="ps-card" aria-labelledby="payment-status-title">
        <div class="ps-mark {{ in_array($state, ['complete', 'attention', 'failed'], true) ? $state : '' }}" id="status-mark">
            <span class="ps-spinner" aria-hidden="true"></span>
            <svg class="ps-icon ps-icon-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
            <svg class="ps-icon ps-icon-alert" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M12 9v4m0 4h.01M10.3 3.6 2.4 17.3A2 2 0 0 0 4.1 20h15.8a2 2 0 0 0 1.7-2.7L13.7 3.6a2 2 0 0 0-3.4 0Z"/></svg>
        </div>

        <div class="ps-kicker">Secure payment update</div>
        <h1 class="ps-title" id="payment-status-title">{{ $paymentStatus['headline'] }}</h1>
        <p class="ps-copy" id="payment-status-copy" aria-live="polite">{{ $paymentStatus['message'] }}</p>

        <div class="ps-reference">
            Booking reference:
            <strong>{{ $booking->booking_ref }}</strong>
        </div>

        <div class="ps-steps" aria-label="Payment and ticketing progress">
            <div class="ps-step done" id="step-returned"><span class="ps-step-dot">✓</span>Returned from SeerBit</div>
            <div class="ps-step {{ $isPaymentConfirmed ? 'done' : 'active' }}" id="step-payment"><span class="ps-step-dot">{{ $isPaymentConfirmed ? '✓' : '2' }}</span>Payment confirmed</div>
            <div class="ps-step {{ $isComplete ? 'done' : ($isPaymentConfirmed ? 'active' : '') }}" id="step-ticket"><span class="ps-step-dot">{{ $isComplete ? '✓' : '3' }}</span>Ticket issued</div>
        </div>

        <div class="ps-note" id="status-note">
            Keep this page open. It checks your booking automatically, so you do not need to refresh or pay again.
        </div>

        <div class="ps-actions {{ in_array($state, ['attention', 'failed'], true) ? 'visible' : '' }}" id="status-actions">
            <button class="ps-btn ps-btn-primary" id="check-status" type="button">Check status again</button>
            <a class="ps-btn ps-btn-secondary" href="mailto:info@travelwheel.ng?subject=Flight payment {{ urlencode($booking->booking_ref) }}">Contact support</a>
        </div>
    </section>
</main>

<script>
(() => {
    const endpoint = @json(route('payments.seerbit.status'));
    const title = document.getElementById('payment-status-title');
    const copy = document.getElementById('payment-status-copy');
    const mark = document.getElementById('status-mark');
    const note = document.getElementById('status-note');
    const actions = document.getElementById('status-actions');
    const checkButton = document.getElementById('check-status');
    const paymentStep = document.getElementById('step-payment');
    const ticketStep = document.getElementById('step-ticket');
    let checks = 0;
    let timer;

    const setStep = (element, state, label) => {
        element.classList.remove('done', 'active');
        if (state) element.classList.add(state);
        element.querySelector('.ps-step-dot').textContent = label;
    };

    const render = (status) => {
        title.textContent = status.headline;
        copy.textContent = status.message;
        mark.className = 'ps-mark' + (['complete', 'attention', 'failed'].includes(status.state) ? ` ${status.state}` : '');

        const paymentConfirmed = ['ticketing', 'complete', 'attention'].includes(status.state);
        setStep(paymentStep, paymentConfirmed ? 'done' : 'active', paymentConfirmed ? '✓' : '2');
        setStep(ticketStep, status.state === 'complete' ? 'done' : (paymentConfirmed ? 'active' : ''), status.state === 'complete' ? '✓' : '3');

        if (status.state === 'complete' && status.redirect_url) {
            note.textContent = 'Success. Opening your ticket confirmation now…';
            window.setTimeout(() => window.location.replace(status.redirect_url), 450);
            return false;
        }

        if (['attention', 'failed', 'missing'].includes(status.state)) {
            note.textContent = status.state === 'attention'
                ? 'Do not pay again. TravelWheel has your payment record and will follow up on ticket issuance.'
                : 'Do not retry payment if you were debited. Contact support with the booking reference shown above.';
            actions.classList.add('visible');
            return false;
        }

        return true;
    };

    const check = async (manual = false) => {
        window.clearTimeout(timer);
        if (manual) {
            actions.classList.remove('visible');
            note.textContent = 'Checking the latest payment and ticketing status…';
        }

        try {
            const response = await fetch(endpoint, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                cache: 'no-store',
            });
            const status = await response.json();
            checks += 1;

            if (render(status)) {
                timer = window.setTimeout(check, checks < 60 ? 2000 : 10000);
            }
        } catch (error) {
            note.textContent = 'The status check was interrupted. We will try again automatically; your payment record is not affected.';
            timer = window.setTimeout(check, 5000);
        }
    };

    checkButton.addEventListener('click', () => check(true));
    timer = window.setTimeout(check, 1000);
})();
</script>

@endcomponent
