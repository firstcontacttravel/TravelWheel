<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>E-Ticket — {{ $bookingRef }}</title>
<style>
    /* ─── Reset & Base ──────────────────────────────────────────────────── */
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 11px;
        color: #1E293B;
        background: #ffffff;
        line-height: 1.4;
    }

    /* ─── Page wrapper ──────────────────────────────────────────────────── */
    .page { width: 80%; padding-left: 10%; padding-right: 10%; margin: 20px 0; }

    /* ─── Header ───────────────────────────────────────────────────────── */
    .hdr {
        background: #0a1940;
        padding: 16px 22px;
    }
    .hdr-inner { width: 100%; }
    .hdr-inner td { vertical-align: middle; }

    .brand {
        color: #ffffff;
        font-size: 20px;
        font-weight: 700;
        letter-spacing: -0.3px;
    }
    .brand-sub {
        color: #2DD4BF;
        font-size: 8px;
        font-weight: 700;
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
    .issued {
        color: rgba(255,255,255,0.45);
        font-size: 8px;
        margin-top: 5px;
        text-align: right;
    }
    .issued strong { color: rgba(255,255,255,0.8); }

    /* ─── Reference Strip ───────────────────────────────────────────────── */
    .ref-strip { width: 100%; border-collapse: collapse; border-bottom: 1px solid #E2E8F0; }
    .ref-block {
        width: 33.33%;
        padding: 14px 18px;
        border-right: 1px solid #E2E8F0;
        vertical-align: top;
    }
    .ref-block.last { border-right: none; }
    .ref-lbl {
        font-size: 7.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #94A3B8;
        margin-bottom: 4px;
    }
    .ref-val {
        font-size: 14px;
        font-weight: 700;
        color: #0F172A;
        font-family: DejaVu Sans Mono, monospace;
        letter-spacing: 0.5px;
    }
    .ref-val.pnr { color: #2563EB; }
    .ref-sub {
        font-size: 7.5px;
        color: #64748B;
        margin-top: 3px;
    }

    /* ─── Confirm strip ─────────────────────────────────────────────────── */
    .confirm-strip {
        background: #F0FDF4;
        border-bottom: 1.5px solid #BBF7D0;
        padding: 9px 22px;
        font-size: 8.5px;
        color: #059669;
        font-weight: 700;
    }
    .confirm-strip table { width: 100%; }
    .confirm-strip .right { color: #94A3B8; font-weight: 400; text-align: right; }

    /* ─── E-ticket bar ──────────────────────────────────────────────────── */
    @if($isTicketed && !empty($passengers) && count($passengers) > 1)
    .eticket-bar {
        background: #EEF2FF;
        border-bottom: 1px solid #C7D2FE;
        padding: 10px 22px;
    }
    .eticket-bar-label {
        font-size: 7.5px;
        font-weight: 700;
        color: #4F46E5;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
    }
    .eticket-num {
        font-size: 12px;
        font-weight: 700;
        color: #0F172A;
        font-family: DejaVu Sans Mono, monospace;
    }
    .eticket-pax-name {
        font-size: 8px;
        color: #64748B;
        margin-top: 1px;
    }
    @endif

    /* ─── Section labels ────────────────────────────────────────────────── */
    .section-label {
        font-size: 7.5px;
        font-weight: 700;
        color: #94A3B8;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        padding: 12px 18px 5px;
    }

    /* ─── Flight Card ───────────────────────────────────────────────────── */
    .flight-card {
        margin: 0 14px 10px;
        border: 1px solid #E2E8F0;
        border-radius: 8px;
        overflow: hidden;
        padding: 10px;
    }
    .fc-top { padding: 9px 13px 8px; border-bottom: 1px dashed #E2E8F0; }
    .fc-top table { width: 100%; }
    .fc-top td { vertical-align: middle; }

    .leg-badge {
        font-size: 7px;
        font-weight: 700;
        color: #ffffff;
        padding: 3px 9px;
        border-radius: 4px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
    .badge-outbound { background: #0D9488; }
    .badge-return   { background: #2563EB; }
    .badge-leg      { background: #7C3AED; }

    .fnum  { font-size: 12px; font-weight: 700; color: #0F172A; margin-left: 8px; }
    .fmeta { font-size: 8px; color: #64748B; margin-left: 8px; margin-top: 1px; }
    .fdate { font-size: 9px; font-weight: 700; color: #0D9488; text-align: right; }

    /* Route row */
    .route-row { padding: 14px 13px 12px; border-bottom: 1px dashed #E2E8F0; }
    .route-table { width: 100%; }
    .route-table td { vertical-align: middle; }

    .iata { font-size: 28px; font-weight: 700; color: #0F172A; line-height: 1; }
    .city  { font-size: 7.5px; color: #94A3B8; margin-top: 2px; }
    .dep-arr-time { font-size: 11px; font-weight: 700; color: #1E293B; margin-top: 3px; }

    .arrow-col { text-align: center; width: 38%; }
    .dur-label  { font-size: 8px; font-weight: 700; color: #0D9488; margin-bottom: 4px; }
    .arrow-line { border-top: 1px dashed #CBD5E1; margin: 0 6px; }
    .stops-label { font-size: 7.5px; color: #94A3B8; margin-top: 4px; }

    /* Flight footer detail row */
    .fc-foot { padding: 8px 13px; background: #F8FAFC; }
    .fc-foot table { width: 100%; }
    .fc-foot td { width: 25%; padding-right: 8px; vertical-align: top; }
    .dl { font-size: 7px; color: #94A3B8; font-weight: 700; text-transform: uppercase; }
    .dv { font-size: 8.5px; font-weight: 700; color: #0F172A; margin-top: 2px; }

    /* ─── Pending notice ────────────────────────────────────────────────── */
    .pending-notice {
        margin: 0 14px 10px;
        background: #FFFBEB;
        border: 1px solid #FCD34D;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 8.5px;
        color: #92400E;
    }
    .pending-notice strong { font-weight: 700; }

    /* ─── Passenger table ───────────────────────────────────────────────── */
    .pax-table {
        width: calc(100% - 28px);
        margin: 0 14px 10px;
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
    .bottom-grid { width: calc(100% - 28px); margin: 0 14px 10px; border-collapse: collapse; }
    .bottom-grid td { vertical-align: top; }

    .info-card {
        border: 1px solid #E2E8F0;
        border-radius: 8px;
        overflow: hidden;
    }
    .ic-head {
        background: #0F172A;
        padding: 8px 12px;
        font-size: 9.5px;
        font-weight: 700;
        color: #ffffff;
    }
    .ic-body { padding: 10px 12px; }

    .fare-row { margin-bottom: 6px; }
    .fare-row table { width: 100%; }
    .fare-lbl { font-size: 8px; color: #64748B; }
    .fare-val { font-size: 8px; color: #1E293B; font-weight: 600; text-align: right; }
    .fare-divider { border: none; border-top: 1px dashed #E2E8F0; margin: 7px 0; }
    .fare-total-lbl { font-size: 10px; font-weight: 700; color: #0F172A; }
    .fare-total-val { font-size: 10px; font-weight: 700; color: #0F172A; text-align: right; }

    .contact-lbl { font-size: 7.5px; color: #94A3B8; text-transform: uppercase; font-weight: 700; }
    .contact-val { font-size: 9px; color: #1E293B; margin-top: 1px; font-weight: 600; margin-bottom: 8px; }

    /* ─── Reminders ─────────────────────────────────────────────────────── */
    .reminders {
        margin: 0 14px 10px;
        background: #FFFBEB;
        border: 1px solid #FDE68A;
        border-radius: 8px;
        padding: 10px 14px;
    }
    .reminders-title {
        font-size: 8px;
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
        padding: 10px 22px;
        margin-top: 4px;
    }
    .footer table { width: 100%; }
    .footer-left  { font-size: 7.5px; color: rgba(255,255,255,0.45); }
    .footer-right { font-size: 7.5px; color: rgba(255,255,255,0.45); text-align: right; }
    .footer-right strong { color: rgba(255,255,255,0.8); font-family: DejaVu Sans Mono, monospace; }
</style>
</head>
<body>
<div class="page">

    {{-- ══ HEADER ══ --}}
    <div class="hdr">
        <table class="hdr-inner">
            <tr>
                <td>
                    <div class="brand">TravelWheel</div>
                    <div class="brand-sub">Electronic Ticket</div>
                </td>
                <td style="text-align:right;">
                    <span class="status-pill {{ $isTicketed ? '' : 'pending' }}">
                        {{ $isTicketed ? '✓ Ticketed' : '⏳ Confirmed' }}
                    </span>
                    <div class="issued">
                        Booking Ref: <strong>{{ $bookingRef }}</strong>
                        &nbsp;·&nbsp; Issued: <strong>{{ now()->format('d M Y, H:i') }}</strong>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ══ REFERENCE STRIP (Booking Ref · E-Ticket · PNR) ══ --}}
    <table class="ref-strip">
        <tr>
            <td class="ref-block">
                <div class="ref-lbl">Booking Reference</div>
                <div class="ref-val">{{ $bookingRef }}</div>
                <div class="ref-sub">Quote this ref for all support enquiries</div>
            </td>
            <td class="ref-block">
                <div class="ref-lbl">E-Ticket Number{{ count($passengers) > 1 ? '(s)' : '' }}</div>
                @if(!empty($passengers))
                    @foreach($passengers as $pax)
                        <div class="ref-val" style="font-size:{{ count($passengers) > 1 ? '11px' : '14px' }};">
                            {{ $pax['eticket'] ?? ($isTicketed ? '—' : 'Pending') }}
                        </div>
                        @if(count($passengers) > 1)
                        <div class="ref-sub">{{ $pax['title'] ?? '' }} {{ strtoupper($pax['first_name'] ?? '') }} {{ strtoupper($pax['last_name'] ?? '') }}</div>
                        @endif
                    @endforeach
                @else
                    <div class="ref-val">—</div>
                @endif
                <div class="ref-sub" style="margin-top:4px;">Present at check-in counter</div>
            </td>
            <td class="ref-block last">
                <div class="ref-lbl">Airline PNR</div>
                <div class="ref-val pnr">{{ $pnr ?? '—' }}</div>
                <div class="ref-sub">For online check-in &amp; seat selection</div>
            </td>
        </tr>
    </table>

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

    {{-- ══ MULTI-PAX E-TICKET BAR (only shown when 2+ passengers) ══ --}}
    @if($isTicketed && !empty($passengers) && count($passengers) > 1)
    <div class="eticket-bar">
        <div class="eticket-bar-label">All E-Ticket Numbers</div>
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

    {{-- ══ OUTBOUND FLIGHTS ══ --}}
    @if(!empty($outboundSegments))
    <div class="section-label">Outbound Flight{{ count($outboundSegments) > 1 ? 's' : '' }}</div>
    @foreach($outboundSegments as $seg)
        @include('pdf.partials.flight-segment', ['seg' => $seg, 'badge' => 'outbound'])
    @endforeach
    @endif

    {{-- ══ RETURN FLIGHTS ══ --}}
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
                'ADT' => 'type-adult',
                'CHD' => 'type-child',
                'INF' => 'type-inf',
                default => 'type-adult'
            };
            $typeLabel = match($pax['type'] ?? 'ADT') {
                'ADT' => 'Adult',
                'CHD' => 'Child',
                'INF' => 'Infant',
                default => 'Pax'
            };
        @endphp
        <tr>
            <td style="color:#94A3B8;">{{ $i + 1 }}</td>
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

    {{-- ══ FARE SUMMARY + CONTACT ══ --}}
    <table class="bottom-grid">
        <tr>
            <td style="width:49%; padding-right:8px;">
                <div class="info-card">
                    <div class="ic-head">Fare Summary</div>
                    <div class="ic-body">
                        @foreach($fareBreakdown as $fb)
                        @php
                            $ptLabel = match($fb['passengerType'] ?? 'ADT') {
                                'ADT' => 'Adult', 'CHD' => 'Child', 'INF' => 'Infant', default => 'Passenger'
                            };
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
                        <div style="margin-top:6px; font-size:7.5px; color:#64748B;">
                            Payment: <strong>{{ ucfirst(str_replace('_', ' ', $paymentMethod)) }}</strong>
                        </div>
                    </div>
                </div>
            </td>

            <td style="width:49%; padding-left:8px;">
                <div class="info-card">
                    <div class="ic-head">Contact &amp; Support</div>
                    <div class="ic-body">
                        <div class="contact-lbl">Email</div>
                        <div class="contact-val">{{ $contactEmail }}</div>
                        <div class="contact-lbl">Phone</div>
                        <div class="contact-val">{{ $contactPhone }}</div>
                        <hr class="fare-divider">
                        <div class="contact-lbl">Support Email</div>
                        <div class="contact-val">support@travelwheel.com</div>
                        <div class="contact-lbl">Support Hotline</div>
                        <div class="contact-val">+234 800 000 0000 &nbsp;(Mon–Fri 8am–6pm)</div>
                        <div style="margin-top:8px; font-size:7.5px; color:#64748B; line-height:1.5;">
                            Always quote your booking reference when contacting support:<br>
                            <strong style="font-family:DejaVu Sans Mono,monospace; color:#0F172A; font-size:9px;">{{ $bookingRef }}</strong>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- ══ REMINDERS ══ --}}
    <div class="reminders">
        <div class="reminders-title">Important Reminders</div>
        <div class="reminder-item">
            <strong>Check-in:</strong> Arrive at least <strong>2 hours</strong> before domestic and <strong>3 hours</strong> before international flights.
        </div>
        <div class="reminder-item">
            <strong>Valid ID:</strong> Carry a valid photo ID or passport. Your name must match exactly as printed on this ticket.
        </div>
        <div class="reminder-item">
            <strong>Baggage:</strong> Check your airline's baggage policy. Excess baggage fees apply at the airport.
        </div>
        <div class="reminder-item">
            <strong>Online Check-in:</strong> Most airlines open online check-in 24–48 hours before departure.
            Use your Airline PNR <strong>{{ $pnr ?? '' }}</strong> on the airline's website or app.
        </div>
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