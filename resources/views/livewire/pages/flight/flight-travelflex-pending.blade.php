{{-- resources/views/livewire/pages/flight/flight-travelflex-pending.blade.php --}}
@component('layouts.app', ['title' => 'TravelFlex — Plan Pending Activation'])

@php
    $flight       = session('bookingFlight.flight') ?? session('bookingFlight', []);
    $segments     = $flight['segments'] ?? [];
    $firstSeg     = $segments[0] ?? [];
    $lastSeg      = !empty($segments) ? $segments[count($segments)-1] : [];
    $currency     = $flight['currency'] ?? 'NGN';
    $sym          = match($currency) { 'NGN' => '₦', 'USD' => '$', 'GBP' => '£', 'EUR' => '€', default => $currency.' ' };
    $fmt          = fn($v) => $sym . number_format((float)$v, 2);
    $totalPrice   = (float)($flight['price'] ?? 0);
    $contact      = session('bookingContact', []);
    $passengers   = session('bookingPassengers', []);
    $uniqueId     = session('bookingUniqueId', '');
    $tktTimeLimit = session('bookingTktTimeLimit', '');
    $tfPlan       = session('travelFlexPlan', []);

    $downPayment   = (float)($tfPlan['down_payment']   ?? 0);
    $downPercent   = (int)  ($tfPlan['down_percent']   ?? 30);
    $repaymentPlan = $tfPlan['repayment_plan']          ?? '';
    $grandTotal    = (float)($tfPlan['grand_total']    ?? 0);
    $totalInterest = (float)($tfPlan['total_interest'] ?? 0);
    $schedule      = $tfPlan['schedule']               ?? [];

    $tktFormatted = '';
    if ($tktTimeLimit) {
        try { $tktFormatted = \Carbon\Carbon::parse($tktTimeLimit)->format('D, d M Y \a\t H:i'); } catch (\Throwable $e) {}
    }
    $departDateLabel = $firstSeg['departDate'] ?? '';
@endphp

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{--navy:#0a1940;--blue:#1d4ed8;--blue-lt:#eff6ff;--blue-md:#bfdbfe;--indigo:#4338ca;--green:#059669;--green-lt:#f0fdf4;--amber:#d97706;--amber-lt:#fff7ed;--purple:#7c3aed;--purple-lt:#f5f3ff;--gray-50:#f8fafc;--gray-100:#f1f5f9;--gray-200:#e2e8f0;--gray-400:#94a3b8;--gray-500:#64748b;--gray-700:#334155;--gray-900:#0f172a;--font:'Plus Jakarta Sans',sans-serif;--mono:'DM Mono',monospace}
    body{font-family:var(--font);background:var(--gray-50);color:var(--gray-900);font-size:14px;margin-top:110px}
    .tfp-wrap{max-width:880px;margin:0 auto;padding:28px 16px 80px}
    /* Hero */
    .tfp-hero{background:linear-gradient(135deg,#1e3a5f 0%,var(--indigo) 100%);border-radius:16px;padding:28px 28px;margin-bottom:22px;color:#fff;display:flex;align-items:flex-start;gap:18px}
    .tfp-hero-icon{font-size:48px;flex-shrink:0}
    .tfp-hero-title{font-size:20px;font-weight:800;margin-bottom:6px; color:white;}
    .tfp-hero-sub{font-size:13px;opacity:.88;line-height:1.65;max-width:520px; color:white;}
    .tfp-badge{display:inline-flex;align-items:center;gap:7px;margin-top:10px;padding:7px 14px;background:rgba(255,255,255,.15);color:white;border-radius:8px;font-size:13px;font-weight:700;font-family:var(--mono)}
    /* Timeline */
    .tfp-timeline{background:#fff;border:1px solid var(--gray-200);border-radius:14px;overflow:hidden;margin-bottom:20px;box-shadow:0 1px 4px rgba(0,0,0,.06)}
    .tfp-tl-head{padding:13px 20px;background:var(--gray-50);border-bottom:1px solid var(--gray-100);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--gray-400)}
    .tfp-tl-step{display:flex;align-items:flex-start;gap:16px;padding:15px 20px;border-bottom:1px solid var(--gray-100)}
    .tfp-tl-step:last-child{border-bottom:none}
    .tfp-tl-num{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;flex-shrink:0;margin-top:1px}
    .tfp-tl-num.done{background:var(--green);color:#fff}
    .tfp-tl-num.current{background:var(--amber);color:#fff;box-shadow:0 0 0 4px rgba(217,119,6,.2)}
    .tfp-tl-num.pending{background:var(--gray-100);color:var(--gray-400);border:2px solid var(--gray-200)}
    .tfp-tl-title{font-size:13.5px;font-weight:700;color:var(--gray-900);margin-bottom:3px}
    .tfp-tl-sub{font-size:12px;color:var(--gray-500);line-height:1.55}
    /* Cards grid */
    .tfp-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px}
    .tfp-card{background:#fff;border:1px solid var(--gray-200);border-radius:12px;padding:18px;box-shadow:0 1px 3px rgba(0,0,0,.05)}
    .tfp-card-title{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--gray-400);margin-bottom:12px}
    .tfp-row{display:flex;align-items:flex-start;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--gray-100);font-size:12.5px;gap:10px}
    .tfp-row:last-child{border-bottom:none}
    .tfp-lbl{color:var(--gray-500)}
    .tfp-val{font-weight:700;text-align:right}
    /* Instalment table */
    .tfp-schedule-card{background:#fff;border:1px solid var(--gray-200);border-radius:12px;overflow:hidden;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,.05)}
    .tfp-schedule-head{padding:13px 18px;background:linear-gradient(135deg,var(--navy) 0%,var(--indigo) 100%)}
    .tfp-schedule-title{font-size:13px;font-weight:800;color:#fff}
    .tfp-table{width:100%;border-collapse:collapse;font-size:12.5px}
    .tfp-table th{padding:9px 14px;text-align:left;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--gray-400);background:var(--gray-50);border-bottom:1px solid var(--gray-200)}
    .tfp-table td{padding:11px 14px;border-bottom:1px solid var(--gray-100);color:var(--gray-700)}
    .tfp-table tr:last-child td{border-bottom:none}
    .tfp-due-badge{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;background:var(--blue-lt);color:var(--blue);border-radius:999px;font-size:10.5px;font-weight:700}
    /* Deadline */
    .tfp-deadline{background:var(--amber-lt);border:1px solid #fed7aa;border-radius:12px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;gap:14px}
    /* Notice */
    .tfp-notice{display:flex;align-items:flex-start;gap:9px;padding:11px 14px;border-radius:9px;font-size:12.5px;margin-bottom:16px}
    .tfp-notice.info{background:var(--blue-lt);color:var(--blue);border:1px solid var(--blue-md)}
    .tfp-notice.green{background:var(--green-lt);color:var(--green);border:1px solid #a7f3d0}
    .tfp-notice.purple{background:var(--purple-lt);color:var(--purple);border:1px solid #ddd6fe}
    /* Pax table */
    .tfp-pax-table{width:100%;border-collapse:collapse;font-size:12.5px;background:#fff;border-radius:12px;overflow:hidden;border:1px solid var(--gray-200);margin-bottom:20px}
    .tfp-pax-table thead tr{background:var(--gray-50)}
    .tfp-pax-table th{padding:9px 14px;text-align:left;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--gray-400);border-bottom:1px solid var(--gray-200)}
    .tfp-pax-table td{padding:10px 14px;border-bottom:1px solid var(--gray-100);color:var(--gray-700)}
    .tfp-pax-table tr:last-child td{border-bottom:none}
    /* Buttons */
    .tfp-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:22px}
    .tfp-btn-primary{padding:0 28px;height:48px;background:var(--blue);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:var(--font);text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:background .15s}
    .tfp-btn-primary:hover{background:#1e40af}
    .tfp-btn-ghost{padding:0 22px;height:48px;background:#fff;border:1.5px solid var(--gray-200);border-radius:10px;font-size:13.5px;font-weight:700;color:var(--gray-700);text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:all .15s}
    .tfp-btn-ghost:hover{background:var(--gray-50);border-color:var(--gray-400)}
    @media(max-width:700px){.tfp-grid{grid-template-columns:1fr}.tfp-hero{flex-direction:column;gap:12px}}
    @media(max-width:580px){.tfp-wrap{padding:12px 10px 60px}}
</style>

<div class="tfp-wrap">

    {{-- Hero --}}
    <div class="tfp-hero">
        <div class="tfp-hero-icon">📆</div>
        <div>
            <div class="tfp-hero-title">TravelFlex Plan — Awaiting Payment Verification</div>
            <div class="tfp-hero-sub">
                Thank you! We've received your down payment notification.
                Our team will verify your transfer and activate your TravelFlex plan within <strong style="color:white; font-weight:700;">2–4 business hours</strong>.
                Your booking will then be confirmed and your e-ticket issued.
            </div>
            @if($uniqueId)
            <div class="tfp-badge">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                Booking Ref: {{ $uniqueId }}
            </div>
            @endif
        </div>
    </div>

    {{-- Timeline --}}
    <div class="tfp-timeline">
        <div class="tfp-tl-head">What happens next</div>
        <div class="tfp-tl-step"><div class="tfp-tl-num done">✓</div><div><div class="tfp-tl-title">Flight Reserved</div><div class="tfp-tl-sub">Your seat is on hold with the airline. Ref: <strong>{{ $uniqueId }}</strong></div></div></div>
        <div class="tfp-tl-step"><div class="tfp-tl-num done">✓</div><div><div class="tfp-tl-title">TravelFlex Plan Created</div><div class="tfp-tl-sub">Your {{ $repaymentPlan }} instalment plan has been set up. Down payment: <strong>{{ $fmt($downPayment) }}</strong></div></div></div>
        <div class="tfp-tl-step"><div class="tfp-tl-num done">✓</div><div><div class="tfp-tl-title">Down Payment Notified</div><div class="tfp-tl-sub">You've confirmed your bank transfer has been made.</div></div></div>
        <div class="tfp-tl-step"><div class="tfp-tl-num current">⏳</div><div><div class="tfp-tl-title">Payment Verification <span style="font-size:11px;background:var(--amber-lt);color:var(--amber);padding:2px 8px;border-radius:999px;font-weight:700;margin-left:6px;">In Progress</span></div><div class="tfp-tl-sub">Our team is verifying your bank transfer. Expected: <strong>2–4 business hours</strong> (Mon–Fri, 8am–6pm).</div></div></div>
        <div class="tfp-tl-step"><div class="tfp-tl-num pending">5</div><div><div class="tfp-tl-title">E-Ticket Issued &amp; Plan Activated</div><div class="tfp-tl-sub">Once confirmed, your ticket will be emailed to <strong>{{ $contact['email'] ?? 'you' }}</strong> and your repayment schedule will begin.</div></div></div>
    </div>

    {{-- Ticketing Deadline --}}
    @if($tktFormatted)
    <div class="tfp-deadline">
        <div style="font-size:28px;flex-shrink:0">⏰</div>
        <div>
            <div style="font-size:13.5px;font-weight:800;color:#92400e;margin-bottom:3px">Important: Booking Hold Expires</div>
            <div style="font-size:12px;color:#78350f;line-height:1.55">Your seat reservation expires on <strong>{{ $tktFormatted }}</strong>. Please ensure your payment is verified before this time.</div>
        </div>
    </div>
    @endif

    {{-- Info cards --}}
    <div class="tfp-grid">
        <div class="tfp-card">
            <div class="tfp-card-title">Booking Details</div>
            @if($uniqueId)<div class="tfp-row"><span class="tfp-lbl">Reference</span><span class="tfp-val" style="color:var(--blue);font-family:var(--mono)">{{ $uniqueId }}</span></div>@endif
            <div class="tfp-row"><span class="tfp-lbl">Route</span><span class="tfp-val">{{ ($firstSeg['from']??'') }} → {{ ($lastSeg['to']??'') }}</span></div>
            @if($departDateLabel)<div class="tfp-row"><span class="tfp-lbl">Travel Date</span><span class="tfp-val">{{ $departDateLabel }}</span></div>@endif
            <div class="tfp-row"><span class="tfp-lbl">Airline</span><span class="tfp-val">{{ $flight['airline'] ?? '—' }}</span></div>
            <div class="tfp-row"><span class="tfp-lbl">Cabin</span><span class="tfp-val">{{ $flight['cabin'] ?? 'Economy' }}</span></div>
            <div class="tfp-row"><span class="tfp-lbl">Ticket Cost</span><span class="tfp-val" style="font-family:var(--mono)">{{ $fmt($totalPrice) }}</span></div>
        </div>
        <div class="tfp-card">
            <div class="tfp-card-title">TravelFlex Plan</div>
            <div class="tfp-row"><span class="tfp-lbl">Down Payment</span><span class="tfp-val" style="color:var(--green);font-family:var(--mono)">{{ $fmt($downPayment) }} ({{ $downPercent }}%)</span></div>
            <div class="tfp-row"><span class="tfp-lbl">Repayment Plan</span><span class="tfp-val">{{ $repaymentPlan }}</span></div>
            <div class="tfp-row"><span class="tfp-lbl">Instalments</span><span class="tfp-val">{{ count($schedule) }} payment(s)</span></div>
            <div class="tfp-row"><span class="tfp-lbl">Total Interest</span><span class="tfp-val" style="font-family:var(--mono)">{{ $fmt($totalInterest) }}</span></div>
            <div class="tfp-row"><span class="tfp-lbl">Payment Method</span><span class="tfp-val">Bank Transfer</span></div>
            <div class="tfp-row" style="border-top:2px solid var(--gray-200);padding-top:10px;margin-top:4px">
                <span class="tfp-lbl" style="font-weight:800;color:var(--gray-900)">Total Payable</span>
                <span class="tfp-val" style="font-size:15px;color:var(--navy);font-family:var(--mono)">{{ $fmt($grandTotal) }}</span>
            </div>
        </div>
    </div>

    {{-- Instalment Schedule --}}
    @if(!empty($schedule))
    <div class="tfp-schedule-card">
        <div class="tfp-schedule-head">
            <div class="tfp-schedule-title">📅 Your Repayment Schedule</div>
        </div>
        <table class="tfp-table">
            <thead>
                <tr><th>#</th><th>Instalment</th><th>Due Date</th><th>Principal</th><th>Interest</th><th>Total Due</th></tr>
            </thead>
            <tbody>
                @foreach($schedule as $i => $inst)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td><strong>{{ $inst['label'] ?? ($i+1).'. Payment' }}</strong></td>
                    <td><span class="tfp-due-badge">{{ $inst['dueDate'] ?? '—' }}</span></td>
                    <td>{{ $fmt($inst['principal'] ?? 0) }}</td>
                    <td style="color:var(--amber)">{{ $fmt($inst['interest'] ?? 0) }}</td>
                    <td><strong style="color:var(--indigo)">{{ $fmt($inst['total'] ?? 0) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Notices --}}
    <div class="tfp-notice purple">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        <span>Your repayment schedule above will begin from the date your plan is activated. You will receive a reminder email before each payment is due.</span>
    </div>

    <div class="tfp-notice info">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>Your e-ticket and plan confirmation will be emailed to <strong>{{ $contact['email'] ?? '' }}</strong>.
        @if(!empty($contact['phone'])) We may contact you on <strong>{{ $contact['phone'] }}</strong>. @endif</span>
    </div>

    {{-- Passenger Table --}}
    @if(!empty($passengers))
    <table class="tfp-pax-table">
        <thead><tr><th>#</th><th>Name</th><th>Type</th><th>Date of Birth</th><th>Nationality</th><th>Passport</th></tr></thead>
        <tbody>
            @foreach($passengers as $i => $pax)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td><strong>{{ $pax['title']??'' }} {{ strtoupper($pax['first_name']??'') }} {{ strtoupper($pax['last_name']??'') }}</strong></td>
                <td>@php $b=match($pax['type']??'ADT'){'ADT'=>['Adult','#dbeafe','#1d4ed8'],'CHD'=>['Child','#fef3c7','#d97706'],'INF'=>['Infant','#f0fdf4','#059669'],default=>['Pax','#f1f5f9','#64748b']}; @endphp<span style="padding:2px 10px;border-radius:999px;font-size:11px;font-weight:700;background:{{$b[1]}};color:{{$b[2]}}">{{$b[0]}}</span></td>
                <td>{{ !empty($pax['dob']) ? \Carbon\Carbon::parse($pax['dob'])->format('d M Y') : '—' }}</td>
                <td>{{ $pax['nationality'] ?? '—' }}</td>
                <td>{{ $pax['passport_no'] ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="tfp-notice green">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.27h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 17z"/></svg>
        <span>Need help? Contact us Mon–Fri 8am–6pm. Email: <strong>support@travelwheel.com</strong> | Tel: <strong>+234 800 000 0000</strong>. Quote ref: <strong>{{ $uniqueId }}</strong>.</span>
    </div>

    <div class="tfp-actions">
        <a href="{{ route('home') }}" class="tfp-btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Back to Home
        </a>
        <a href="#" onclick="window.print()" class="tfp-btn-ghost">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Print / Save
        </a>
    </div>

</div>
@endcomponent