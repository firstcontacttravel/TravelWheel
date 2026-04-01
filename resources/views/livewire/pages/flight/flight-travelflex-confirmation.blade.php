{{-- resources/views/livewire/pages/flight/flight-travelflex-confirmation.blade.php --}}
@component('layouts.app', ['title' => 'TravelFlex — Plan Confirmed!'])

@php
    $flight        = session('bookingFlight.flight') ?? session('bookingFlight', []);
    $segments      = $flight['segments'] ?? [];
    $firstSeg      = $segments[0] ?? [];
    $lastSeg       = !empty($segments) ? $segments[count($segments)-1] : [];
    $currency      = $flight['currency'] ?? 'NGN';
    $sym           = match($currency) { 'NGN' => '₦', 'USD' => '$', 'GBP' => '£', 'EUR' => '€', default => $currency.' ' };
    $fmt           = fn($v) => $sym . number_format((float)$v, 2);
    $totalPrice    = (float)($flight['price'] ?? 0);
    $contact       = session('bookingContact', []);
    $passengers    = session('bookingPassengers', []);
    $uniqueId      = session('bookingUniqueId', '');
    $bookingResult = session('bookingConfirmation', []);
    $tfPlan        = session('travelFlexPlan', []);
    $dbBooking     = session('flightBookingDbId') ? \App\Models\FlightBooking::find(session('flightBookingDbId')) : null;

    $downPayment   = (float)($tfPlan['down_payment']   ?? 0);
    $downPercent   = (int)  ($tfPlan['down_percent']   ?? 30);
    $repaymentPlan = $tfPlan['repayment_plan']          ?? '';
    $grandTotal    = (float)($tfPlan['grand_total']    ?? 0);
    $totalInterest = (float)($tfPlan['total_interest'] ?? 0);
    $schedule      = $tfPlan['schedule']               ?? [];
    $remainingBal  = $totalPrice - $downPayment;

    $departDateLabel = $firstSeg['departDate'] ?? '';

    // Confirm API success
    $apiStatus  = data_get($bookingResult, 'BookFlightResponse.BookFlightResult.Status', '');
    $apiSuccess = filter_var(data_get($bookingResult, 'BookFlightResponse.BookFlightResult.Success', false), FILTER_VALIDATE_BOOLEAN);
@endphp

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{--navy:#0a1940;--blue:#1d4ed8;--blue-lt:#eff6ff;--blue-md:#bfdbfe;--indigo:#4338ca;--green:#059669;--green-lt:#f0fdf4;--amber:#d97706;--amber-lt:#fff7ed;--purple:#7c3aed;--purple-lt:#f5f3ff;--gray-50:#f8fafc;--gray-100:#f1f5f9;--gray-200:#e2e8f0;--gray-400:#94a3b8;--gray-500:#64748b;--gray-700:#334155;--gray-900:#0f172a;--font:'Plus Jakarta Sans',sans-serif;--mono:'DM Mono',monospace}
    body{font-family:var(--font);background:var(--gray-50);color:var(--gray-900);font-size:14px;margin-top:110px}
    .tfc-wrap{max-width:880px;margin:0 auto;padding:28px 16px 80px}
    /* Hero */
    .tfc-hero{background:linear-gradient(135deg,#064e3b 0%,var(--indigo) 60%,var(--purple) 100%);border-radius:16px;padding:32px 28px;margin-bottom:24px;color:#fff;display:flex;align-items:flex-start;gap:20px;position:relative;overflow:hidden}
    .tfc-hero::before{content:'';position:absolute;top:-50px;right:-50px;width:220px;height:220px;background:radial-gradient(circle,rgba(255,255,255,.12) 0%,transparent 70%);pointer-events:none}
    .tfc-hero-icon{font-size:52px;flex-shrink:0;position:relative;z-index:2}
    .tfc-hero-title{font-size:22px;font-weight:800;margin-bottom:6px;position:relative;z-index:2;color:white;}
    .tfc-hero-sub{font-size:13.5px;opacity:.88;line-height:1.65;max-width:500px;position:relative;z-index:2;color:white;}
    .tfc-badge{display:inline-flex;align-items:center;gap:7px;margin-top:12px;padding:8px 16px;background:rgba(255,255,255,.15);color:white;border-radius:8px;font-size:13px;font-weight:700;font-family:var(--mono);position:relative;z-index:2}
    /* Cards grid */
    .tfc-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px}
    .tfc-card{background:#fff;border:1px solid var(--gray-200);border-radius:12px;padding:18px;box-shadow:0 1px 3px rgba(0,0,0,.05)}
    .tfc-card-title{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--gray-400);margin-bottom:12px}
    .tfc-row{display:flex;align-items:flex-start;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--gray-100);font-size:12.5px;gap:10px}
    .tfc-row:last-child{border-bottom:none}
    .tfc-lbl{color:var(--gray-500)}
    .tfc-val{font-weight:700;text-align:right}
    /* Schedule card */
    .tfc-schedule-card{background:#fff;border:1px solid var(--gray-200);border-radius:12px;overflow:hidden;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,.05)}
    .tfc-schedule-head{padding:14px 18px;background:linear-gradient(135deg,var(--navy) 0%,var(--indigo) 100%);display:flex;align-items:center;justify-content:space-between}
    .tfc-schedule-title{font-size:13px;font-weight:800;color:#fff}
    .tfc-schedule-sub{font-size:11px;color:rgba(255,255,255,.75)}
    .tfc-table{width:100%;border-collapse:collapse;font-size:12.5px}
    .tfc-table th{padding:9px 14px;text-align:left;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--gray-400);background:var(--gray-50);border-bottom:1px solid var(--gray-200)}
    .tfc-table td{padding:11px 14px;border-bottom:1px solid var(--gray-100);color:var(--gray-700)}
    .tfc-table tr:last-child td{border-bottom:none}
    .tfc-due-badge{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;background:var(--blue-lt);color:var(--blue);border-radius:999px;font-size:10.5px;font-weight:700}
    /* Total bar */
    .tfc-total{display:flex;justify-content:space-between;align-items:center;padding:16px 18px;background:linear-gradient(135deg,var(--navy) 0%,var(--indigo) 100%);border-radius:12px;margin-bottom:16px}
    .tfc-total-lbl{font-size:14px;font-weight:800;color:#fff}
    .tfc-total-val{font-size:22px;font-weight:800;color:#fff;font-family:var(--mono)}
    /* Notice */
    .tfc-notice{display:flex;align-items:flex-start;gap:9px;padding:11px 14px;border-radius:9px;font-size:12.5px;margin-bottom:16px}
    .tfc-notice.info{background:var(--blue-lt);color:var(--blue);border:1px solid var(--blue-md)}
    .tfc-notice.green{background:var(--green-lt);color:var(--green);border:1px solid #a7f3d0}
    .tfc-notice.purple{background:var(--purple-lt);color:var(--purple);border:1px solid #ddd6fe}
    /* Pax table */
    .tfc-pax-table{width:100%;border-collapse:collapse;font-size:12.5px;background:#fff;border-radius:12px;overflow:hidden;border:1px solid var(--gray-200);margin-bottom:20px}
    .tfc-pax-table thead tr{background:var(--gray-50)}
    .tfc-pax-table th{padding:9px 14px;text-align:left;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--gray-400);border-bottom:1px solid var(--gray-200)}
    .tfc-pax-table td{padding:10px 14px;border-bottom:1px solid var(--gray-100);color:var(--gray-700)}
    .tfc-pax-table tr:last-child td{border-bottom:none}
    /* Buttons */
    .tfc-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:22px}
    .tfc-btn-primary{padding:0 28px;height:48px;background:var(--blue);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:var(--font);text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:background .15s}
    .tfc-btn-primary:hover{background:#1e40af}
    .tfc-btn-ghost{padding:0 22px;height:48px;background:#fff;border:1.5px solid var(--gray-200);border-radius:10px;font-size:13.5px;font-weight:700;color:var(--gray-700);text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:all .15s}
    .tfc-btn-ghost:hover{background:var(--gray-50);border-color:var(--gray-400)}
    @media(max-width:700px){.tfc-grid{grid-template-columns:1fr}.tfc-hero{flex-direction:column;gap:12px}}
    @media(max-width:580px){.tfc-wrap{padding:12px 10px 60px}}
</style>

<div class="tfc-wrap">

    {{-- Hero --}}
    <div class="tfc-hero">
        <div class="tfc-hero-icon">🎉</div>
        <div>
            <div class="tfc-hero-title">TravelFlex Plan Activated!</div>
            <div class="tfc-hero-sub">
                Your down payment has been processed and your booking is confirmed.
                Your e-ticket has been issued and sent to <strong style="color:white;font-weight:700;">{{ $contact['email'] ?? 'your email' }}</strong>.
                Follow your repayment schedule below to keep your booking active.
            </div>
            @if($uniqueId)
            <div class="tfc-badge">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                Booking Ref: {{ $uniqueId }}
            </div>
            @endif
        </div>
    </div>

    {{-- Info cards --}}
    <div class="tfc-grid">
        <div class="tfc-card">
            <div class="tfc-card-title">✈️ Flight Confirmed</div>
            @if($uniqueId)<div class="tfc-row"><span class="tfc-lbl">Reference</span><span class="tfc-val" style="color:var(--blue);font-family:var(--mono)">{{ $uniqueId }}</span></div>@endif
            <div class="tfc-row"><span class="tfc-lbl">Route</span><span class="tfc-val">{{ ($firstSeg['from']??'') }} → {{ ($lastSeg['to']??'') }}</span></div>
            @if($departDateLabel)<div class="tfc-row"><span class="tfc-lbl">Travel Date</span><span class="tfc-val">{{ $departDateLabel }}</span></div>@endif
            <div class="tfc-row"><span class="tfc-lbl">Airline</span><span class="tfc-val">{{ $flight['airline'] ?? '—' }}</span></div>
            <div class="tfc-row"><span class="tfc-lbl">Cabin</span><span class="tfc-val">{{ $flight['cabin'] ?? 'Economy' }}</span></div>
            <div class="tfc-row"><span class="tfc-lbl">Ticket Status</span><span class="tfc-val" style="color:var(--green)">✓ Issued</span></div>
        </div>
        <div class="tfc-card">
            <div class="tfc-card-title">📆 TravelFlex Plan</div>
            <div class="tfc-row"><span class="tfc-lbl">Ticket Cost</span><span class="tfc-val" style="font-family:var(--mono)">{{ $fmt($totalPrice) }}</span></div>
            <div class="tfc-row"><span class="tfc-lbl">Down Payment Paid</span><span class="tfc-val" style="color:var(--green);font-family:var(--mono)">{{ $fmt($downPayment) }} ({{ $downPercent }}%)</span></div>
            <div class="tfc-row"><span class="tfc-lbl">Remaining Balance</span><span class="tfc-val" style="font-family:var(--mono)">{{ $fmt($remainingBal) }}</span></div>
            <div class="tfc-row"><span class="tfc-lbl">Repayment Plan</span><span class="tfc-val">{{ $repaymentPlan }}</span></div>
            <div class="tfc-row"><span class="tfc-lbl">Total Interest</span><span class="tfc-val" style="font-family:var(--mono)">{{ $fmt($totalInterest) }}</span></div>
            <div class="tfc-row" style="border-top:2px solid var(--gray-200);padding-top:10px;margin-top:4px">
                <span class="tfc-lbl" style="font-weight:800;color:var(--gray-900)">Total Payable</span>
                <span class="tfc-val" style="font-size:15px;color:var(--navy);font-family:var(--mono)">{{ $fmt($grandTotal) }}</span>
            </div>
        </div>
    </div>

    {{-- Total bar --}}
    <div class="tfc-total">
        <span class="tfc-total-lbl">💰 Down Payment Paid Today</span>
        <span class="tfc-total-val">{{ $fmt($downPayment) }}</span>
    </div>

    {{-- Repayment Schedule --}}
    @if(!empty($schedule))
    <div class="tfc-schedule-card">
        <div class="tfc-schedule-head">
            <div>
                <div class="tfc-schedule-title">📅 Your Repayment Schedule</div>
                <div class="tfc-schedule-sub">{{ count($schedule) }} instalment(s) · {{ $repaymentPlan }}</div>
            </div>
        </div>
        <table class="tfc-table">
            <thead>
                <tr><th>#</th><th>Instalment</th><th>Due Date</th><th>Principal</th><th>Interest</th><th>Total Due</th><th>Status</th></tr>
            </thead>
            <tbody>
                @foreach($schedule as $i => $inst)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td><strong>{{ $inst['label'] ?? ($i+1).'. Payment' }}</strong></td>
                    <td><span class="tfc-due-badge">{{ $inst['dueDate'] ?? '—' }}</span></td>
                    <td>{{ $fmt($inst['principal'] ?? 0) }}</td>
                    <td style="color:var(--amber)">{{ $fmt($inst['interest'] ?? 0) }}</td>
                    <td><strong style="color:var(--indigo)">{{ $fmt($inst['total'] ?? 0) }}</strong></td>
                    <td><span style="padding:2px 8px;border-radius:999px;font-size:10.5px;font-weight:700;background:var(--amber-lt);color:var(--amber)">Upcoming</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Notices --}}
    <div class="tfc-notice purple">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        <span>You will receive a <strong>reminder email</strong> before each instalment is due. Ensure payments are made on time to keep your booking active. Missed payments may result in cancellation.</span>
    </div>

    <div class="tfc-notice green">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
        <span>Your e-ticket has been sent to <strong>{{ $contact['email'] ?? '' }}</strong>. Bring a valid photo ID matching your ticket name to the airport.</span>
    </div>

    {{-- Passengers --}}
    @if(!empty($passengers))
    <table class="tfc-pax-table">
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

    <div class="tfc-notice info">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>Need help? Email <strong>support@travelwheel.com</strong> or call <strong>+234 800 000 0000</strong>. Quote ref: <strong>{{ $uniqueId }}</strong>.</span>
    </div>

    <div class="tfc-actions">
        <a href="{{ route('home') }}" class="tfc-btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Back to Home
        </a>
        <a href="#" onclick="window.print()" class="tfc-btn-ghost">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Print / Save
        </a>
    </div>

</div>
@endcomponent