<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>E-Ticket — {{ $bookingRef }}</title>
<style>
    /* ─── Reset & Base ─────────────────────────────────────────────────── */
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: DejaVu Sans, sans-serif; /* DomPDF-safe font */
        font-size: 11px;
        color: #1E293B;
        background: #ffffff;
        line-height: 1.4;
    }

    /* ─── Colour tokens ─────────────────────────────────────────────────── */
    /* (defined inline since DomPDF doesn't support CSS variables) */

    /* ─── Page wrapper ──────────────────────────────────────────────────── */
    .page { width: 100%; padding: 0; }

    /* ─── Header band ───────────────────────────────────────────────────── */
    .header {
        background: #0F172A;
        padding: 18px 28px;
        margin-bottom: 0;
    }
    .header-inner { width: 100%; }
    .header td { vertical-align: middle; }
    .brand-name {
        font-size: 22px;
        font-weight: 700;
        color: #ffffff;
        letter-spacing: -0.3px;
    }
    .brand-sub {
        font-size: 9px;
        font-weight: 700;
        color: #0D9488;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-top: 2px;
    }
    .status-pill {
        background: #059669;
        color: #ffffff;
        font-size: 9px;
        font-weight: 700;
        padding: 5px 14px;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .status-pill.pending {
        background: #D97706;
    }
    .header-ref {
        color: rgba(255,255,255,0.55);
        font-size: 8px;
        margin-top: 4px;
    }
    .header-ref strong { color: #ffffff; }

    /* ─── Confirm strip ─────────────────────────────────────────────────── */
    .confirm-strip {
        background: #F0FDF4;
        border-bottom: 1.5px solid #BBF7D0;
        padding: 9px 28px;
        font-size: 9px;
        color: #059669;
        font-weight: 700;
    }
    .confirm-strip table { width: 100%; }
    .confirm-strip .right { color: #94A3B8; font-weight: 400; text-align: right; }

    /* ─── E-ticket bar ──────────────────────────────────────────────────── */
    .eticket-bar {
        background: #EEF2FF;
        border-bottom: 1px solid #C7D2FE;
        padding: 10px 28px;
    }
    .eticket-bar-label {
        font-size: 8px;
        font-weight: 700;
        color: #4F46E5;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
    }
    .eticket-item {
        display: inline-block;
        margin-right: 24px;
    }
    .eticket-num {
        font-size: 13px;
        font-weight: 700;
        color: #0F172A;
        font-family: DejaVu Sans Mono, monospace;
    }
    .eticket-pax-name {
        font-size: 8px;
        color: #64748B;
        margin-top: 1px;
    }

    /* ─── Section label ─────────────────────────────────────────────────── */
    .section-label {
        font-size: 8px;
        font-weight: 700;
        color: #94A3B8;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        padding: 12px 28px 5px;
    }

    /* ─── Flight card ───────────────────────────────────────────────────── */
    .flight-card {
        margin: 0 20px 10px;
        border: 1px solid #E2E8F0;
        border-radius: 8px;
        background: #ffffff;
        overflow: hidden;
    }
    .flight-card-top {
        padding: 10px 14px 8px;
        border-bottom: 1px dashed #E2E8F0;
    }
    .flight-card-top table { width: 100%; }
    .leg-badge {
        font-size: 7.5px;
        font-weight: 700;
        color: #ffffff;
        padding: 3px 10px;
        border-radius: 4px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
    .badge-outbound { background: #0D9488; }
    .badge-return   { background: #2563EB; }
    .badge-leg      { background: #7C3AED; }

    .flight-num {
        font-size: 12px;
        font-weight: 700;
        color: #0F172A;
        margin-left: 8px;
    }
    .flight-meta {
        font-size: 8px;
        color: #64748B;
        margin-left: 8px;
        margin-top: 1px;
    }
    .flight-date {
        font-size: 9px;
        font-weight: 700;
        color: #0D9488;
        text-align: right;
    }

    /* Route row */
    .route-row {
        padding: 12px 14px 10px;
        border-bottom: 1px dashed #E2E8F0;
    }
    .route-table { width: 100%; }
    .iata {
        font-size: 30px;
        font-weight: 700;
        color: #0F172A;
        line-height: 1;
    }
    .city-name { font-size: 8px; color: #94A3B8; margin-top: 2px; }
    .dep-arr-time { font-size: 11px; font-weight: 700; color: #1E293B; margin-top: 3px; }

    .arrow-col { text-align: center; width: 40%; }
    .arrow-line {
        border-top: 1px dashed #CBD5E1;
        margin: 0 6px;
        position: relative;
    }
    .duration-label { font-size: 8px; font-weight: 700; color: #0D9488; margin-bottom: 3px; }
    .stops-label    { font-size: 7.5px; color: #94A3B8; margin-top: 3px; }
    .plane-glyph    { font-size: 16px; color: #0D9488; }

    /* Footer detail row */
    .flight-footer {
        padding: 8px 14px;
        background: #F8FAFC;
    }
    .detail-cell { width: 25%; padding-right: 8px; }
    .detail-lbl  { font-size: 7px; color: #94A3B8; font-weight: 700; text-transform: uppercase; }
    .detail-val  { font-size: 8.5px; font-weight: 700; color: #0F172A; margin-top: 2px; }

    /* ─── Passenger table ───────────────────────────────────────────────── */
    .pax-table {
        width: calc(100% - 40px);
        margin: 0 20px 10px;
        border-collapse: collapse;
        border: 1px solid #E2E8F0;
        border-radius: 8px;
        overflow: hidden;
        font-size: 8.5px;
    }
    .pax-table thead tr { background: #0F172A; }
    .pax-table thead th {
        color: #ffffff;
        font-size: 7.5px;
        font-weight: 700;
        text-transform: uppercase;
        padding: 7px 10px;
        text-align: left;
        letter-spacing: 0.3px;
    }
    .pax-table tbody tr:nth-child(odd)  { background: #F8FAFC; }
    .pax-table tbody tr:nth-child(even) { background: #ffffff; }
    .pax-table tbody td {
        padding: 7px 10px;
        color: #1E293B;
        border-top: 1px solid #F1F5F9;
    }
    .pax-name { font-weight: 700; font-size: 9px; }
    .pax-type-badge {
        font-size: 7px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 10px;
    }
    .type-adult { background: #DBEAFE; color: #1D4ED8; }
    .type-child { background: #FEF3C7; color: #D97706; }
    .type-inf   { background: #F0FDF4; color: #059669; }
    .mono       { font-family: DejaVu Sans Mono, monospace; font-size: 8px; }

    /* ─── Bottom two-col grid ───────────────────────────────────────────── */
    .bottom-grid { width: calc(100% - 40px); margin: 0 20px 10px; }
    .bottom-grid td { vertical-align: top; }

    .info-card {
        border: 1px solid #E2E8F0;
        border-radius: 8px;
        overflow: hidden;
    }
    .info-card-head {
        background: #0F172A;
        padding: 8px 12px;
        font-size: 10px;
        font-weight: 700;
        color: #ffffff;
    }
    .info-card-body { padding: 10px 12px; }

    .fare-row { margin-bottom: 7px; }
    .fare-row table { width: 100%; }
    .fare-lbl { font-size: 8px; color: #64748B; }
    .fare-val { font-size: 8px; color: #1E293B; font-weight: 600; text-align: right; }
    .fare-divider { border: none; border-top: 1px dashed #E2E8F0; margin: 8px 0; }
    .fare-total-lbl { font-size: 10px; font-weight: 700; color: #0F172A; }
    .fare-total-val { font-size: 10px; font-weight: 700; color: #0F172A; text-align: right; }

    .contact-row { margin-bottom: 7px; }
    .contact-lbl { font-size: 7.5px; color: #94A3B8; text-transform: uppercase; font-weight: 700; }
    .contact-val { font-size: 9px; color: #1E293B; margin-top: 1px; font-weight: 600; }

    /* ─── Reminders ─────────────────────────────────────────────────────── */
    .reminders {
        margin: 0 20px 10px;
        background: #FFFBEB;
        border: 1px solid #FDE68A;
        border-radius: 8px;
        padding: 10px 14px;
    }
    .reminders-title {
        font-size: 8.5px;
        font-weight: 700;
        color: #92400E;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
    .reminder-item { margin-bottom: 4px; font-size: 8px; color: #78350F; }
    .reminder-item strong { color: #92400E; }

    /* ─── Footer ────────────────────────────────────────────────────────── */
    .footer {
        background: #0F172A;
        padding: 10px 28px;
        margin-top: 4px;
    }
    .footer table { width: 100%; }
    .footer-left  { font-size: 7.5px; color: rgba(255,255,255,0.5); }
    .footer-right { font-size: 7.5px; color: rgba(255,255,255,0.5); text-align: right; }
    .footer-right strong { color: rgba(255,255,255,0.8); font-family: DejaVu Sans Mono, monospace; }

    /* ─── Pending notice ────────────────────────────────────────────────── */
    .pending-notice {
        margin: 0 20px 10px;
        background: #FFFBEB;
        border: 1px solid #FCD34D;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 8.5px;
        color: #92400E;
    }
    .pending-notice strong { font-weight: 700; }
</style>
</head>
<body>
<div class="page">

    {{-- ══ HEADER ══ --}}
    <div class="header">
        <table class="header-inner">
            <tr>
                <td>
                    <div class="brand-name">TravelWheel</div>
                    <div class="brand-sub">✈ Electronic Ticket</div>
                </td>
                <td style="text-align:right;">
                    <span class="status-pill {{ $isTicketed ? '' : 'pending' }}">
                        {{ $isTicketed ? '✓ Ticketed' : '⏳ Confirmed' }}
                    </span>
                    <div class="header-ref" style="margin-top:6px;">
                        Booking Ref: <strong>{{ $bookingRef }}</strong>
                        &nbsp;·&nbsp; Issued: <strong>{{ now()->format('d M Y, H:i') }}</strong>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ══ CONFIRM STRIP ══ --}}
    <div class="confirm-strip">
        <table>
            <tr>
                <td>
                    @if($isTicketed)
                        ✓ &nbsp;Booking Confirmed &amp; E-Ticket Issued — Present this document at the check-in counter
                    @else
                        ⏳ &nbsp;Booking Confirmed — E-ticket is being processed and will be emailed shortly
                    @endif
                </td>
                <td class="right">
                    {{ $tripLabel }} &nbsp;·&nbsp; {{ $cabin }} &nbsp;·&nbsp; {{ $airline }}
                </td>
            </tr>
        </table>
    </div>

    {{-- ══ E-TICKET BAR ══ --}}
    @if($isTicketed && !empty($passengers))
    <div class="eticket-bar">
        <div class="eticket-bar-label">E-Ticket Number(s)</div>
        <table><tr>
        @foreach($passengers as $pax)
        <td style="padding-right:28px;">
            <div class="eticket-num">{{ $pax['eticket'] ?? '—' }}</div>
            <div class="eticket-pax-name">{{ $pax['title'] ?? '' }} {{ strtoupper($pax['first_name'] ?? '') }} {{ strtoupper($pax['last_name'] ?? '') }}</div>
        </td>
        @endforeach
        </tr></table>
    </div>
    @endif

    {{-- ══ OUTBOUND FLIGHT ══ --}}
    @if(!empty($outboundSegments))
    <div class="section-label">Outbound Flight{{ count($outboundSegments) > 1 ? 's' : '' }}</div>
    @foreach($outboundSegments as $seg)
    @include('pdf.partials.flight-segment', ['seg' => $seg, 'badge' => 'outbound'])
    @endforeach
    @endif

    {{-- ══ RETURN FLIGHT ══ --}}
    @if(!empty($returnSegments))
    <div class="section-label">Return Flight{{ count($returnSegments) > 1 ? 's' : '' }}</div>
    @foreach($returnSegments as $seg)
    @include('pdf.partials.flight-segment', ['seg' => $seg, 'badge' => 'return'])
    @endforeach
    @endif

    {{-- ══ MULTI-CITY LEGS ══ --}}
    @if(!empty($multiLegs))
    @foreach($multiLegs as $li => $leg)
    <div class="section-label">Leg {{ $li + 1 }}: {{ $leg['from'] ?? '' }} → {{ $leg['to'] ?? '' }}</div>
    @foreach($leg['segments'] ?? [] as $seg)
    @include('pdf.partials.flight-segment', ['seg' => $seg, 'badge' => 'leg'])
    @endforeach
    @endforeach
    @endif

    {{-- ══ PENDING NOTICE ══ --}}
    @if(!$isTicketed)
    <div class="pending-notice">
        <strong>⏳ Ticketing in Progress:</strong>
        Your seat is reserved. Your e-ticket will be emailed to <strong>{{ $contactEmail }}</strong>
        within 15–30 minutes. Quote your booking reference <strong>{{ $bookingRef }}</strong>
        if you need to contact support before receiving your ticket.
    </div>
    @endif

    {{-- ══ PASSENGERS ══ --}}
    @if(!empty($passengers))
    <div class="section-label">Passenger Details</div>
    <table class="pax-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Passenger Name</th>
                <th>Type</th>
                <th>Date of Birth</th>
                <th>Nationality</th>
                <th>Passport No.</th>
                <th>E-Ticket No.</th>
            </tr>
        </thead>
        <tbody>
        @foreach($passengers as $i => $pax)
        @php
            $typeClass = match($pax['type'] ?? 'ADT') {
                'ADT' => 'type-adult', 'CHD' => 'type-child', 'INF' => 'type-inf', default => 'type-adult'
            };
            $typeLabel = match($pax['type'] ?? 'ADT') {
                'ADT' => 'Adult', 'CHD' => 'Child', 'INF' => 'Infant', default => 'Pax'
            };
        @endphp
        <tr>
            <td style="color:#94A3B8">{{ $i + 1 }}</td>
            <td class="pax-name">
                {{ $pax['title'] ?? '' }} {{ strtoupper($pax['first_name'] ?? '') }} {{ strtoupper($pax['last_name'] ?? '') }}
            </td>
            <td><span class="pax-type-badge {{ $typeClass }}">{{ $typeLabel }}</span></td>
            <td>{{ !empty($pax['dob']) ? \Carbon\Carbon::parse($pax['dob'])->format('d M Y') : '—' }}</td>
            <td>{{ $pax['nationality'] ?? '—' }}</td>
            <td class="mono">{{ $pax['passport_no'] ?? '—' }}</td>
            <td class="mono">{{ $pax['eticket'] ?? ($isTicketed ? '—' : 'Pending') }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    @endif

    {{-- ══ FARE + CONTACT ══ --}}
    <table class="bottom-grid">
        <tr>
            {{-- Fare Summary --}}
            <td style="width:49%;padding-right:8px;">
                <div class="info-card">
                    <div class="info-card-head">Fare Summary</div>
                    <div class="info-card-body">
                        @foreach($fareBreakdown as $fb)
                        @php
                            $ptLabel = match($fb['passengerType'] ?? 'ADT'){'ADT'=>'Adult','CHD'=>'Child','INF'=>'Infant',default=>'Passenger'};
                            $qty = $fb['qty'] ?? 1;
                        @endphp
                        <div class="fare-row">
                            <table>
                                <tr>
                                    <td class="fare-lbl">{{ $ptLabel }} × {{ $qty }}</td>
                                    <td class="fare-val">{{ $currencySymbol }}{{ number_format(($fb['totalFare'] ?? 0) * $qty, 2) }}</td>
                                </tr>
                            </table>
                        </div>
                        @endforeach
                        <hr class="fare-divider">
                        <table>
                            <tr>
                                <td class="fare-total-lbl">Total Paid</td>
                                <td class="fare-total-val">{{ $currencySymbol }}{{ number_format($totalAmount, 2) }}</td>
                            </tr>
                        </table>
                        <div style="margin-top:6px;font-size:7.5px;color:#64748B;">
                            Payment: <strong>{{ ucfirst(str_replace('_', ' ', $paymentMethod)) }}</strong>
                        </div>
                    </div>
                </div>
            </td>

            {{-- Contact & Support --}}
            <td style="width:49%;padding-left:8px;">
                <div class="info-card">
                    <div class="info-card-head">Contact &amp; Support</div>
                    <div class="info-card-body">
                        <div class="contact-row">
                            <div class="contact-lbl">Email</div>
                            <div class="contact-val">{{ $contactEmail }}</div>
                        </div>
                        <div class="contact-row">
                            <div class="contact-lbl">Phone</div>
                            <div class="contact-val">{{ $contactPhone }}</div>
                        </div>
                        <hr class="fare-divider">
                        <div class="contact-row">
                            <div class="contact-lbl">Support Email</div>
                            <div class="contact-val">support@travelwheel.com</div>
                        </div>
                        <div class="contact-row">
                            <div class="contact-lbl">Support Hotline</div>
                            <div class="contact-val">+234 800 000 0000 &nbsp;(Mon–Fri 8am–6pm)</div>
                        </div>
                        <div style="margin-top:8px;font-size:7.5px;color:#64748B;line-height:1.5;">
                            Always quote your booking reference when contacting support:<br>
                            <strong style="font-family:DejaVu Sans Mono,monospace;color:#0F172A;font-size:9px;">{{ $bookingRef }}</strong>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- ══ REMINDERS ══ --}}
    <div class="reminders">
        <div class="reminders-title">Important Reminders</div>
        <div class="reminder-item">✈ <strong>Check-in:</strong> Arrive at least <strong>2 hours</strong> before domestic and <strong>3 hours</strong> before international flights.</div>
        <div class="reminder-item">🪪 <strong>Valid ID:</strong> Carry a valid photo ID or passport. Your name must match exactly as printed on this ticket.</div>
        <div class="reminder-item">🧳 <strong>Baggage:</strong> Check your airline's baggage policy. Excess baggage fees apply at the airport.</div>
        <div class="reminder-item">📱 <strong>Online Check-in:</strong> Most airlines open online check-in 24–48 hours before departure. Visit the airline's website.</div>
    </div>

    {{-- ══ FOOTER ══ --}}
    <div class="footer">
        <table>
            <tr>
                <td class="footer-left">
                    This is your official e-ticket. Present it at the airport check-in counter with a valid photo ID or passport.
                </td>
                <td class="footer-right">
                    TravelWheel &nbsp;·&nbsp; <strong>{{ $bookingRef }}</strong> &nbsp;·&nbsp; Generated {{ now()->format('d M Y H:i') }}
                </td>
            </tr>
        </table>
    </div>

</div>
</body>
</html>
