{{-- resources/views/livewire/pages/flight/flight-travelflex-fastcredit.blade.php --}}
@component('layouts.app', ['title' => 'Connecting to FastCredit'])

@php
    $target = $target ?? 'application';
    $targetUrl = $target === 'plan'
        ? route('flights.travelflex')
        : route('flights.travelflex.application.get');
    $headline = $target === 'plan'
        ? 'Preparing your TravelFlex options'
        : 'Connecting you to FastCredit';
    $copy = $target === 'plan'
        ? 'Your flight details are ready. We are opening the TravelFlex planning step before the FastCredit application.'
        : 'Your TravelFlex plan is ready. We are preparing the FastCredit application step and carrying your booking details forward securely.';
    $finalStep = $target === 'plan' ? 'Options loading' : 'Application loading';
@endphp

<meta http-equiv="refresh" content="4.8;url={{ $targetUrl }}">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=DM+Mono:wght@500&display=swap" rel="stylesheet">

<style>
    :root {
        --fc-red:#d8262f;
        --fc-green:#089b62;
        --fc-black:#08090d;
        --fc-ink:#101828;
        --fc-muted:#667085;
        --fc-line:#e7ebf2;
        --fc-bg:#f6f8fb;
        --fc-font:'Plus Jakarta Sans', sans-serif;
        --fc-mono:'DM Mono', monospace;
    }

    body {
        margin-top:0 !important;
        background:linear-gradient(180deg, #fbfcfe 0%, #f3f6f9 100%);
        font-family:var(--fc-font);
        color:var(--fc-ink);
        overflow-x:hidden;
    }

    .fc-stage {
        min-height:100vh;
        display:grid;
        place-items:center;
        padding:28px 16px;
        position:relative;
    }

    .fc-card {
        width:min(540px, 100%);
        background:#fff;
        border:1px solid var(--fc-line);
        border-radius:16px;
        padding:32px 30px;
        box-shadow:0 18px 48px rgba(16,24,40,.08);
        text-align:center;
        position:relative;
        overflow:hidden;
    }

    .fc-kicker {
        display:inline-flex;
        align-items:center;
        gap:8px;
        min-height:30px;
        padding:6px 11px;
        border:1px solid rgba(8,155,98,.18);
        border-radius:999px;
        background:rgba(8,155,98,.08);
        color:var(--fc-green);
        font-size:11px;
        font-weight:800;
        text-transform:uppercase;
        letter-spacing:.08em;
        margin-bottom:22px;
    }

    .fc-kicker::before {
        content:"";
        width:8px;
        height:8px;
        border-radius:999px;
        background:var(--fc-green);
        box-shadow:0 0 0 0 rgba(8,155,98,.32);
        animation:fc-pulse 1.8s ease-out infinite;
    }

    .fc-logo-scene {
        width:min(220px, 62vw);
        min-height:136px;
        margin:0 auto 22px;
        position:relative;
        display:grid;
        place-items:center;
    }

    .fc-logo-scene::before {
        content:"";
        position:absolute;
        inset:16px 0;
        border-radius:999px;
        background:linear-gradient(90deg, rgba(216,38,47,.07), rgba(8,155,98,.08));
        transform:scale(.96);
        animation:fc-breathe 2.8s ease-in-out infinite;
    }

    .fc-logo-shell {
        width:100%;
        min-height:96px;
        display:grid;
        place-items:center;
        padding:18px 20px;
        border-radius:14px;
        background:#fff;
        border:1px solid rgba(231,235,242,.92);
        box-shadow:0 12px 32px rgba(16,24,40,.08);
        transform-origin:center;
        animation:fc-arrive .72s cubic-bezier(.16,1,.3,1) both;
        position:relative;
        z-index:2;
    }

    .fc-logo {
        width:100%;
        height:auto;
        display:block;
        filter:none;
    }

    .fc-title {
        font-size:clamp(22px, 4vw, 30px);
        line-height:1.16;
        font-weight:800;
        letter-spacing:0;
        margin:0 0 10px;
        color:var(--fc-ink);
    }

    .fc-title span {
        color:var(--fc-green);
    }

    .fc-copy {
        max-width:470px;
        margin:0 auto 24px;
        color:var(--fc-muted);
        font-size:14px;
        line-height:1.7;
    }

    .fc-track {
        height:10px;
        border-radius:999px;
        background:#eef1f6;
        overflow:hidden;
        margin:0 auto 16px;
        max-width:420px;
        position:relative;
    }

    .fc-track::before {
        content:"";
        position:absolute;
        inset:0;
        width:42%;
        border-radius:999px;
        background:linear-gradient(90deg, rgba(216,38,47,.78), rgba(8,155,98,.86));
        animation:fc-load 4.45s cubic-bezier(.45,0,.1,1) forwards;
    }

    .fc-steps {
        display:grid;
        grid-template-columns:repeat(3, 1fr);
        gap:8px;
        max-width:450px;
        margin:0 auto 24px;
    }

    .fc-step {
        min-height:48px;
        display:flex;
        align-items:center;
        justify-content:center;
        padding:8px;
        border:1px solid var(--fc-line);
        border-radius:10px;
        background:#fff;
        color:#475467;
        font-size:11px;
        font-weight:800;
        animation:fc-step 4.45s ease-in-out infinite;
    }

    .fc-step:nth-child(2) { animation-delay:.75s; }
    .fc-step:nth-child(3) { animation-delay:1.5s; }

    .fc-actions {
        display:flex;
        justify-content:center;
    }

    .fc-continue {
        display:inline-flex;
        align-items:center;
        justify-content:center;
        min-height:44px;
        padding:0 18px;
        border-radius:10px;
        background:#111827;
        color:#fff;
        font-size:13px;
        font-weight:800;
        text-decoration:none;
        box-shadow:0 14px 30px rgba(16,24,40,.16);
    }

    @keyframes fc-arrive {
        0% { opacity:0; transform:translateY(10px) scale(.97); filter:blur(4px); }
        100% { opacity:1; transform:translateY(0) scale(1) rotate(0); }
    }

    @keyframes fc-breathe {
        0%, 100% { opacity:.64; transform:scale(.96); }
        50% { opacity:1; transform:scale(1); }
    }

    @keyframes fc-load {
        0% { width:0; transform:translateX(0); }
        72% { width:78%; }
        100% { width:100%; }
    }

    @keyframes fc-step {
        0%, 100% { border-color:var(--fc-line); color:#475467; transform:translateY(0); }
        18%, 38% { border-color:rgba(8,155,98,.28); color:var(--fc-green); transform:translateY(-1px); box-shadow:0 8px 18px rgba(8,155,98,.06); }
    }

    @keyframes fc-pulse {
        100% { box-shadow:0 0 0 12px rgba(8,155,98,0); }
    }

    @media (max-width:640px) {
        .fc-card { padding:26px 18px; border-radius:18px; }
        .fc-logo-scene { width:min(220px, 82vw); }
        .fc-steps { grid-template-columns:1fr; }
    }

    @media (prefers-reduced-motion:reduce) {
        *, *::before, *::after {
            animation-duration:.01ms !important;
            animation-iteration-count:1 !important;
            scroll-behavior:auto !important;
        }
    }
</style>

<main class="fc-stage" aria-live="polite">
    <section class="fc-card">
        <div class="fc-kicker">Secure handoff</div>

        <div class="fc-logo-scene" aria-hidden="true">
            <div class="fc-logo-shell">
                <img class="fc-logo" src="{{ asset('assets/img/fastcredit.png') }}" alt="">
            </div>
        </div>

        <h1 class="fc-title">{{ $headline }}</h1>
        <p class="fc-copy">
            {{ $copy }}
        </p>

        <div class="fc-track" role="progressbar" aria-label="Preparing FastCredit application"></div>
        <div class="fc-steps">
            <div class="fc-step">Booking secured</div>
            <div class="fc-step">Plan encrypted</div>
            <div class="fc-step">{{ $finalStep }}</div>
        </div>

        <div class="fc-actions">
            <a class="fc-continue" href="{{ $targetUrl }}">Continue now</a>
        </div>
    </section>
</main>

<script>
    window.setTimeout(function() {
        window.location.assign(@js($targetUrl));
    }, 4500);
</script>
@endcomponent
