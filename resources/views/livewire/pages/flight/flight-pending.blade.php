{{-- resources/views/livewire/pages/flight/flight-pending.blade.php --}}
@component('layouts.app', ['title' => 'Booking Pending Confirmation'])

@php
    $currency    = $flight['currency']  ?? ($dbBooking?->currency ?? 'NGN');
    $sym         = match($currency) { 'NGN' => '₦', 'USD' => '$', 'GBP' => '£', 'EUR' => '€', default => $currency.' ' };
    $fmt         = fn($v) => $sym . number_format((float)$v, 2);
    $total       = $flight['price']     ?? ($dbBooking?->total_price ?? 0);
    $segments    = $flight['segments']  ?? [];
    $firstSeg    = $segments[0] ?? [];
    $lastSeg     = !empty($segments) ? $segments[count($segments)-1] : [];
    $uniqueId    = $uniqueId            ?? $dbBooking?->unique_id ?? '';
    $breakdown   = $flight['fareBreakdown'] ?? [];
    $fareType    = $flight['fareType']  ?? ($dbBooking?->fare_type ?? 'Public');

    // Ticketing deadline
    $tktFormatted = '';
    $tktHours     = 0;
    if ($tktTimeLimit) {
        try {
            $tktDt        = \Carbon\Carbon::parse($tktTimeLimit);
            $tktFormatted = $tktDt->format('D, d M Y \a\t H:i');
            $tktHours     = max(0, (int) now()->diffInHours($tktDt, false));
        } catch (\Throwable $e) {}
    } elseif ($dbBooking?->tkt_time_limit) {
        $tktDt        = $dbBooking->tkt_time_limit;
        $tktFormatted = $tktDt->format('D, d M Y \a\t H:i');
        $tktHours     = max(0, (int) now()->diffInHours($tktDt, false));
    }

    $paxList = $passengers ?? [];
@endphp

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{--navy:#0a1940;--blue:#1d4ed8;--blue-lt:#eff6ff;--blue-md:#bfdbfe;--green:#059669;--green-lt:#f0fdf4;--amber:#d97706;--amber-lt:#fff7ed;--red:#dc2626;--red-lt:#fef2f2;--gray-50:#f8fafc;--gray-100:#f1f5f9;--gray-200:#e2e8f0;--gray-300:#cbd5e1;--gray-400:#94a3b8;--gray-500:#64748b;--gray-700:#334155;--gray-900:#0f172a;--font:'Plus Jakarta Sans',sans-serif;--mono:'DM Mono',monospace}
    body{font-family:var(--font);background:var(--gray-50);color:var(--gray-900);font-size:14px;margin-top:110px}
    .pnd-wrap{max-width:860px;margin:0 auto;padding:28px 16px 80px}
    /* Hero */
    .pnd-hero{background:linear-gradient(135deg,#1e3a5f 0%,#1d4ed8 100%);border-radius:16px;padding:32px 28px;margin-bottom:24px;color:#fff;display:flex;align-items:flex-start;gap:20px}
    .pnd-hero-icon{font-size:52px;flex-shrink:0}
    .pnd-hero-title{font-size:22px;font-weight:800;margin-bottom:6px}
    .pnd-hero-sub{font-size:13.5px;opacity:.88;line-height:1.65;max-width:540px}
    .pnd-ref{display:inline-flex;align-items:center;gap:8px;margin-top:12px;padding:8px 16px;background:rgba(255,255,255,.15);border-radius:8px;font-size:13px;font-weight:700;font-family:var(--mono)}
    /* Timeline */
    .pnd-timeline{display:flex;flex-direction:column;gap:0;margin-bottom:24px;background:#fff;border:1px solid var(--gray-200);border-radius:14px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.06)}
    .pnd-tl-head{padding:14px 20px;background:var(--gray-50);border-bottom:1px solid var(--gray-100);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--gray-400)}
    .pnd-tl-step{display:flex;align-items:flex-start;gap:16px;padding:16px 20px;border-bottom:1px solid var(--gray-100)}
    .pnd-tl-step:last-child{border-bottom:none}
    .pnd-tl-num{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;flex-shrink:0;margin-top:1px}
    .pnd-tl-num.done{background:var(--green);color:#fff}
    .pnd-tl-num.current{background:var(--amber);color:#fff;box-shadow:0 0 0 4px rgba(217,119,6,.2)}
    .pnd-tl-num.pending{background:var(--gray-100);color:var(--gray-400);border:2px solid var(--gray-200)}
    .pnd-tl-title{font-size:13.5px;font-weight:700;color:var(--gray-900);margin-bottom:3px}
    .pnd-tl-sub{font-size:12px;color:var(--gray-500);line-height:1.5}
    /* Info cards */
    .pnd-cards{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px}
    .pnd-card{background:#fff;border:1px solid var(--gray-200);border-radius:12px;padding:18px 18px;box-shadow:0 1px 3px rgba(0,0,0,.05)}
    .pnd-card-title{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--gray-400);margin-bottom:12px}
    .pnd-row{display:flex;align-items:flex-start;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--gray-100);font-size:12.5px;gap:10px}
    .pnd-row:last-child{border-bottom:none}
    .pnd-lbl{color:var(--gray-500)}
    .pnd-val{font-weight:700;text-align:right;font-size:12.5px}
    /* Deadline banner */
    .pnd-deadline{background:var(--amber-lt);border:1px solid #fed7aa;border-radius:12px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;gap:14px}
    .pnd-deadline-icon{font-size:28px;flex-shrink:0}
    .pnd-deadline-title{font-size:13.5px;font-weight:800;color:#92400e;margin-bottom:3px}
    .pnd-deadline-sub{font-size:12px;color:#78350f;line-height:1.5}
    /* Passenger table */
    .pnd-pax-table{width:100%;border-collapse:collapse;font-size:12.5px;background:#fff;border-radius:12px;overflow:hidden;border:1px solid var(--gray-200);margin-bottom:20px}
    .pnd-pax-table thead tr{background:var(--gray-50)}
    .pnd-pax-table th{padding:10px 14px;text-align:left;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--gray-400);border-bottom:1px solid var(--gray-200)}
    .pnd-pax-table td{padding:11px 14px;border-bottom:1px solid var(--gray-100);color:var(--gray-700)}
    .pnd-pax-table tr:last-child td{border-bottom:none}
    /* Actions */
    .pnd-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:24px}
    .pnd-btn-primary{padding:0 28px;height:48px;background:var(--blue);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:var(--font);text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:background .15s}
    .pnd-btn-primary:hover{background:#1e40af}
    .pnd-btn-ghost{padding:0 22px;height:48px;background:#fff;border:1.5px solid var(--gray-200);border-radius:10px;font-size:13.5px;font-weight:700;color:var(--gray-700);text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:all .15s}
    .pnd-btn-ghost:hover{background:var(--gray-50);border-color:var(--gray-400)}
    /* Notice */
    .pnd-notice{display:flex;align-items:flex-start;gap:9px;padding:12px 16px;border-radius:9px;font-size:12.5px;margin-bottom:16px}
    .pnd-notice.info{background:var(--blue-lt);color:var(--blue);border:1px solid var(--blue-md)}
    .pnd-notice.green{background:var(--green-lt);color:var(--green);border:1px solid #a7f3d0}
    @media(max-width:700px){.pnd-cards{grid-template-columns:1fr}.pnd-hero{flex-direction:column;gap:12px}.pnd-hero-title{font-size:18px}}
    @media(max-width:580px){.pnd-wrap{padding:12px 10px 60px}}
</style>

<div class="pnd-wrap">

    {{-- ── Hero ── --}}
    <div class="pnd-hero">
        <div class="pnd-hero-icon">📬</div>
        <div>
            <div class="pnd-hero-title">Payment Received — Awaiting Verification</div>
            <div class="pnd-hero-sub">
                Thank you! We've noted that you've made payment for your booking. 
                Our team will verify your transfer and issue your e-ticket within <strong>2–4 business hours</strong>.
                A confirmation email has been sent to <strong>{{ $contact['email'] ?? '' }}</strong>.
            </div>
            @if($uniqueId)
            <div class="pnd-ref">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                Booking Ref: {{ $uniqueId }}
            </div>
            @endif
        </div>
    </div>

    {{-- ── What Happens Next (Timeline) ── --}}
    <div class="pnd-timeline">
        <div class="pnd-tl-head">What happens next</div>
        <div class="pnd-tl-step">
            <div class="pnd-tl-num done">✓</div>
            <div>
                <div class="pnd-tl-title">Booking Created</div>
                <div class="pnd-tl-sub">Your seats are reserved with the airline. Booking reference: <strong>{{ $uniqueId }}</strong></div>
            </div>
        </div>
        <div class="pnd-tl-step">
            <div class="pnd-tl-num done">✓</div>
            <div>
                <div class="pnd-tl-title">Payment Notified</div>
                <div class="pnd-tl-sub">You've confirmed that payment has been made. We are now waiting to verify your transfer.</div>
            </div>
        </div>
        <div class="pnd-tl-step">
            <div class="pnd-tl-num current">⏳</div>
            <div>
                <div class="pnd-tl-title">Payment Verification <span style="font-size:11px;background:var(--amber-lt);color:var(--amber);padding:2px 8px;border-radius:999px;font-weight:700;margin-left:6px;">In Progress</span></div>
                <div class="pnd-tl-sub">Our team is verifying your bank transfer. This usually takes <strong>2–4 business hours</strong> during working hours (Mon–Fri, 8am–6pm).</div>
            </div>
        </div>
        <div class="pnd-tl-step">
            <div class="pnd-tl-num pending">4</div>
            <div>
                <div class="pnd-tl-title">E-Ticket Issued</div>
                <div class="pnd-tl-sub">Once payment is confirmed, your ticket will be issued and emailed to <strong>{{ $contact['email'] ?? 'you' }}</strong> immediately.</div>
            </div>
        </div>
    </div>

    {{-- ── Ticketing Deadline ── --}}
    @if($tktFormatted)
    <div class="pnd-deadline">
        <div class="pnd-deadline-icon">⏰</div>
        <div>
            <div class="pnd-deadline-title">Important: Ticketing Deadline</div>
            <div class="pnd-deadline-sub">
                Your booking hold expires on <strong>{{ $tktFormatted }}</strong>
                @if($tktHours > 0) ({{ $tktHours }} hours remaining) @endif.
                Please ensure payment is verified before this time, otherwise the seat reservation may be released by the airline.
                If you have any concerns, contact us immediately.
            </div>
        </div>
    </div>
    @endif

    {{-- ── Info Cards ── --}}
    <div class="pnd-cards">
        {{-- Booking Details --}}
        <div class="pnd-card">
            <div class="pnd-card-title">Booking Details</div>
            @if($uniqueId)
            <div class="pnd-row"><span class="pnd-lbl">Reference</span><span class="pnd-val" style="color:var(--blue);font-family:var(--mono)">{{ $uniqueId }}</span></div>
            @endif
            <div class="pnd-row"><span class="pnd-lbl">Route</span><span class="pnd-val">{{ ($firstSeg['from']??'') }} → {{ ($lastSeg['to']??'') }}</span></div>
            @if(!empty($flight['departDateLabel']))<div class="pnd-row"><span class="pnd-lbl">Date</span><span class="pnd-val">{{ $flight['departDateLabel'] }}</span></div>@endif
            <div class="pnd-row"><span class="pnd-lbl">Airline</span><span class="pnd-val">{{ $flight['airline'] ?? '—' }}</span></div>
            <div class="pnd-row"><span class="pnd-lbl">Cabin</span><span class="pnd-val">{{ $flight['cabin'] ?? 'Economy' }}</span></div>
            <div class="pnd-row"><span class="pnd-lbl">Fare Type</span><span class="pnd-val">{{ $fareType }}</span></div>
        </div>

        {{-- Payment Summary --}}
        <div class="pnd-card">
            <div class="pnd-card-title">Payment Summary</div>
            <div class="pnd-row"><span class="pnd-lbl">Method</span><span class="pnd-val">Bank Transfer</span></div>
            <div class="pnd-row"><span class="pnd-lbl">Status</span><span class="pnd-val" style="color:var(--amber)">⏳ Awaiting Verification</span></div>
            @if(!empty($breakdown))
                @foreach($breakdown as $fb)
                    @php $ptype = match($fb['passengerType']??'ADT'){'ADT'=>'Adult','CHD'=>'Child','INF'=>'Infant',default=>'Pax'}; @endphp
                    <div class="pnd-row">
                        <span class="pnd-lbl">{{ $ptype }} × {{ $fb['qty']??1 }}</span>
                        <span class="pnd-val">{{ $sym }}{{ number_format(($fb['totalFare']??0)*($fb['qty']??1), 2) }}</span>
                    </div>
                @endforeach
            @endif
            <div class="pnd-row" style="border-top:2px solid var(--gray-200);padding-top:10px;margin-top:4px">
                <span class="pnd-lbl" style="font-weight:800;color:var(--gray-900)">Total Amount</span>
                <span class="pnd-val" style="font-size:15px;color:var(--navy)">{{ $fmt($total) }}</span>
            </div>
        </div>
    </div>

    {{-- ── Contact Details ── --}}
    <div class="pnd-notice info">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>
            Your e-ticket and booking confirmation will be sent to <strong>{{ $contact['email'] ?? '' }}</strong>.
            @if(!empty($contact['phone'])) We may also contact you on <strong>{{ $contact['phone'] }}</strong> if needed. @endif
        </span>
    </div>

    {{-- ── Passengers Table ── --}}
    @if(!empty($paxList))
    <table class="pnd-pax-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Type</th>
                <th>Date of Birth</th>
                <th>Nationality</th>
                <th>Passport</th>
            </tr>
        </thead>
        <tbody>
            @foreach($paxList as $i => $pax)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td><strong>{{ $pax['title'] ?? '' }} {{ strtoupper($pax['first_name'] ?? '') }} {{ strtoupper($pax['last_name'] ?? '') }}</strong></td>
                <td>
                    @php $badge = match($pax['type']??'ADT'){'ADT'=>['Adult','#dbeafe','#1d4ed8'],'CHD'=>['Child','#fef3c7','#d97706'],'INF'=>['Infant','#f0fdf4','#059669'],default=>['Pax','#f1f5f9','#64748b']}; @endphp
                    <span style="padding:2px 10px;border-radius:999px;font-size:11px;font-weight:700;background:{{ $badge[1] }};color:{{ $badge[2] }}">{{ $badge[0] }}</span>
                </td>
                <td>{{ !empty($pax['dob']) ? \Carbon\Carbon::parse($pax['dob'])->format('d M Y') : '—' }}</td>
                <td>{{ $pax['nationality'] ?? '—' }}</td>
                <td>{{ $pax['passport_no'] ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- ── Need Help? ── --}}
    <div class="pnd-notice green">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.27h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 17z"/></svg>
        <span>
            Need help? Our team is available Mon–Fri 8am–6pm. 
            Email us at <strong>support@travelwheel.com</strong> or call <strong>+234 800 000 0000</strong>.
            Always quote your booking reference <strong>{{ $uniqueId }}</strong>.
        </span>
    </div>

    {{-- ── Actions ── --}}
    <div class="pnd-actions">
        <a href="{{ route('home') }}" class="pnd-btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Back to Home
        </a>
        <a href="#" onclick="window.print()" class="pnd-btn-ghost">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Print / Save
        </a>
    </div>

</div>
@endcomponent