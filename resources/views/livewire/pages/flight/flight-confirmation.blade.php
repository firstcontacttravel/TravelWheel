@component('layouts.app', ['title' => 'Booking Confirmation'])

@php
    $result        = session('bookingConfirmation', []);
    $bookResult    = $result['BookFlightResponse']['BookFlightResult'] ?? $result;
    $status        = $bookResult['Status']       ?? '';
    $success       = filter_var($bookResult['Success'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $uniqueId      = $bookResult['UniqueID']      ?? '';
    $tktTimeLimit  = $bookResult['TktTimeLimit']  ?? '';
    $target        = $bookResult['Target']        ?? '';
    $errors        = $bookResult['Errors']        ?? [];

    // Parse errors — API returns either "" (empty string) or array
    $errorList = [];
    if (is_array($errors)) {
        foreach ($errors as $e) {
            $err = $e['Errors'] ?? $e;
            if (!empty($err['ErrorMessage'])) {
                $errorList[] = ['code' => $err['ErrorCode'] ?? '', 'msg' => $err['ErrorMessage']];
            }
        }
        // Handle single-error structure: Errors.ErrorCode / Errors.ErrorMessage
        if (empty($errorList) && !empty($errors['ErrorCode'])) {
            $errorList[] = ['code' => $errors['ErrorCode'], 'msg' => $errors['ErrorMessage'] ?? ''];
        }
    }

    // Flight summary from session for the confirmation banner
    $flight       = session('bookingFlight', []);
    $mappedFlight = $flight['flight'] ?? $flight;
    $segments     = $mappedFlight['segments'] ?? [];
    $firstSeg     = $segments[0] ?? [];
    $lastSeg      = count($segments) ? $segments[count($segments) - 1] : [];
    $currency     = $mappedFlight['currency'] ?? 'NGN';
    $sym          = $currency === 'NGN' ? '₦' : ($currency === 'USD' ? '$' : $currency . ' ');
    $totalPrice   = $mappedFlight['price'] ?? 0;

    $tktLimitFormatted = '';
    if ($tktTimeLimit) {
        try { $tktLimitFormatted = \Carbon\Carbon::parse($tktTimeLimit)->format('D, d M Y H:i'); } catch (\Throwable $e) {}
    }
@endphp

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

<style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
        --navy:     #0a1940;
        --blue:     #1d4ed8;
        --blue-lt:  #eff6ff;
        --blue-md:  #bfdbfe;
        --green:    #059669;
        --green-lt: #f0fdf4;
        --amber:    #d97706;
        --amber-lt: #fff7ed;
        --red:      #dc2626;
        --red-lt:   #fef2f2;
        --gray-50:  #f8fafc;
        --gray-100: #f1f5f9;
        --gray-200: #e2e8f0;
        --gray-300: #cbd5e1;
        --gray-400: #94a3b8;
        --gray-500: #64748b;
        --gray-700: #334155;
        --gray-900: #0f172a;
        --font:     'Plus Jakarta Sans', sans-serif;
        --mono:     'DM Mono', monospace;
    }
    body { font-family: var(--font); background: var(--gray-50); color: var(--gray-900); font-size: 14px; line-height: 1.6; margin-top: 110px; }
    .cf-wrap  { max-width: 780px; margin: 0 auto; padding: 32px 16px 80px; }
    .cf-back  { display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--blue); font-weight: 600; text-decoration: none; margin-bottom: 24px; }
    .cf-back:hover { text-decoration: underline; }

    /* ── Hero banner ── */
    .cf-hero { border-radius: 16px; padding: 36px 32px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 22px; }
    .cf-hero.success { background: linear-gradient(135deg, #064e3b 0%, #059669 100%); color: #fff; }
    .cf-hero.pending { background: linear-gradient(135deg, #78350f 0%, #d97706 100%); color: #fff; }
    .cf-hero.failed  { background: linear-gradient(135deg, #7f1d1d 0%, #dc2626 100%); color: #fff; }
    .cf-hero-icon  { width: 64px; height: 64px; border-radius: 50%; background: rgba(255,255,255,.18); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 28px; }
    .cf-hero-title { font-size: 22px; font-weight: 800; margin-bottom: 4px; }
    .cf-hero-sub   { font-size: 13.5px; opacity: .85; }
    .cf-ref-tag    { display: inline-flex; align-items: center; gap: 8px; margin-top: 12px; padding: 8px 16px; background: rgba(255,255,255,.15); border-radius: 8px; font-size: 13px; font-weight: 700; font-family: var(--mono); letter-spacing: .04em; }

    /* ── Cards ── */
    .cf-card { background: #fff; border: 1px solid var(--gray-200); border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,.07); margin-bottom: 16px; overflow: hidden; }
    .cf-card-head { padding: 14px 20px; background: var(--gray-50); border-bottom: 1px solid var(--gray-100); display: flex; align-items: center; gap: 10px; }
    .cf-card-icon { width: 32px; height: 32px; border-radius: 8px; background: var(--blue-lt); color: var(--blue); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .cf-card-title { font-size: 14px; font-weight: 800; color: var(--gray-900); }
    .cf-card-body  { padding: 18px 20px; }

    /* Row grid */
    .cf-row  { display: flex; align-items: flex-start; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--gray-100); gap: 16px; font-size: 13px; }
    .cf-row:last-child { border-bottom: none; }
    .cf-lbl  { color: var(--gray-500); font-weight: 500; flex-shrink: 0; }
    .cf-val  { color: var(--gray-900); font-weight: 700; text-align: right; font-family: var(--mono); font-size: 12.5px; }
    .cf-val.normal { font-family: var(--font); font-size: 13px; }

    /* Status badge */
    .cf-status { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; }
    .cf-status.confirmed { background: var(--green-lt); color: var(--green); }
    .cf-status.pending   { background: var(--amber-lt); color: var(--amber); }
    .cf-status.failed    { background: var(--red-lt);   color: var(--red); }

    /* Error list */
    .cf-error-list { list-style: none; }
    .cf-error-item { display: flex; align-items: flex-start; gap: 10px; padding: 10px 14px; background: var(--red-lt); border: 1px solid #fca5a5; border-radius: 9px; margin-bottom: 8px; font-size: 13px; color: var(--red); }
    .cf-error-item:last-child { margin-bottom: 0; }
    .cf-error-code { font-family: var(--mono); font-size: 11px; font-weight: 700; background: rgba(220,38,38,.12); padding: 1px 6px; border-radius: 4px; flex-shrink: 0; margin-top: 1px; }

    /* Raw JSON dump */
    .cf-json { background: #0f172a; border-radius: 10px; padding: 18px; overflow-x: auto; }
    .cf-json pre { font-family: var(--mono); font-size: 12px; color: #94a3b8; line-height: 1.7; white-space: pre-wrap; word-break: break-all; }
    .cf-json-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: var(--gray-400); margin-bottom: 10px; }

    /* Action buttons */
    .cf-actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 24px; }
    .cf-btn-primary { padding: 0 28px; height: 48px; background: var(--blue); color: #fff; border: none; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; font-family: var(--font); text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: background .15s; }
    .cf-btn-primary:hover { background: #1e40af; }
    .cf-btn-ghost  { padding: 0 22px; height: 48px; background: #fff; border: 1.5px solid var(--gray-200); border-radius: 10px; font-size: 13.5px; font-weight: 700; color: var(--gray-700); text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all .15s; }
    .cf-btn-ghost:hover { background: var(--gray-50); border-color: var(--gray-400); }

    /* Ticker / notice */
    .cf-notice { padding: 11px 16px; border-radius: 9px; font-size: 12.5px; display: flex; align-items: flex-start; gap: 9px; margin-bottom: 16px; }
    .cf-notice.warn { background: var(--amber-lt); color: var(--amber); border: 1px solid #fed7aa; }
    .cf-notice.info { background: var(--blue-lt); color: var(--blue); border: 1px solid var(--blue-md); }

    @media (max-width: 580px) { .cf-wrap { padding: 16px 12px 60px; } .cf-hero { flex-direction: column; gap: 14px; padding: 24px 20px; } .cf-hero-title { font-size: 18px; } }
</style>

<div class="cf-wrap">

    <a href="{{ route('home') }}" class="cf-back">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Back to Home
    </a>

    {{-- ── Hero Banner ── --}}
    @if($success && strtoupper($status) === 'CONFIRMED')
        <div class="cf-hero success">
            <div class="cf-hero-icon">✅</div>
            <div>
                <div class="cf-hero-title">Booking Confirmed!</div>
                <div class="cf-hero-sub">Your flight has been booked successfully. Check your email for the e-ticket.</div>
                @if($uniqueId)
                    <div class="cf-ref-tag">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                        Booking Reference: {{ $uniqueId }}
                    </div>
                @endif
            </div>
        </div>

    @elseif(strtoupper($status) === 'PENDING')
        <div class="cf-hero pending">
            <div class="cf-hero-icon">⏳</div>
            <div>
                <div class="cf-hero-title">Booking Pending</div>
                <div class="cf-hero-sub">Your booking is under review. You will receive a confirmation email shortly.</div>
                @if($uniqueId)
                    <div class="cf-ref-tag">Booking Reference: {{ $uniqueId }}</div>
                @endif
            </div>
        </div>

    @else
        <div class="cf-hero failed">
            <div class="cf-hero-icon">❌</div>
            <div>
                <div class="cf-hero-title">Booking Failed</div>
                <div class="cf-hero-sub">We were unable to complete your booking. Please review the details below and try again.</div>
            </div>
        </div>
    @endif

    {{-- ── Pending time limit notice ── --}}
    @if($tktLimitFormatted && strtoupper($status) === 'PENDING')
        <div class="cf-notice warn">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span>This fare must be ticketed by <strong>{{ $tktLimitFormatted }}</strong> or it will be automatically cancelled.</span>
        </div>
    @endif

    {{-- ── Test environment notice ── --}}
    @if(strtolower($target) === 'test')
        <div class="cf-notice info">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span><strong>Test Environment:</strong> This booking was processed on the test server. No real ticket has been issued.</span>
        </div>
    @endif

    {{-- ── Booking Details Card ── --}}
    <div class="cf-card">
        <div class="cf-card-head">
            <div class="cf-card-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
            </div>
            <div class="cf-card-title">Booking Details</div>
        </div>
        <div class="cf-card-body">
            <div class="cf-row">
                <span class="cf-lbl">Status</span>
                <span class="cf-val normal">
                    @if(strtoupper($status) === 'CONFIRMED')
                        <span class="cf-status confirmed">✓ Confirmed</span>
                    @elseif(strtoupper($status) === 'PENDING')
                        <span class="cf-status pending">⏳ Pending</span>
                    @elseif($status)
                        <span class="cf-status failed">✗ {{ $status }}</span>
                    @else
                        <span class="cf-status failed">✗ Failed</span>
                    @endif
                </span>
            </div>
            @if($uniqueId)
            <div class="cf-row">
                <span class="cf-lbl">Booking Reference</span>
                <span class="cf-val" style="font-size:15px;color:var(--blue);">{{ $uniqueId }}</span>
            </div>
            @endif
            @if($tktLimitFormatted)
            <div class="cf-row">
                <span class="cf-lbl">Ticketing Deadline</span>
                <span class="cf-val">{{ $tktLimitFormatted }}</span>
            </div>
            @endif
            <div class="cf-row">
                <span class="cf-lbl">Environment</span>
                <span class="cf-val normal">{{ ucfirst(strtolower($target ?: 'Unknown')) }}</span>
            </div>
        </div>
    </div>
    @if(!empty($tripDetails))
    @php
        $itinerary        = $tripDetails['ItineraryInfo'] ?? [];
        $resItems         = data_get($itinerary, 'ReservationItems', []);
        $customerInfos    = data_get($itinerary, 'CustomerInfos', []);
        $itinPricing      = data_get($itinerary, 'ItineraryPricing', []);
        $bookingStatus    = $tripDetails['BookingStatus'] ?? '';
        $ticketStatus     = $tripDetails['TicketStatus'] ?? '';
    @endphp
 
    <div class="cf-card">
        <div class="cf-card-head">
            <div class="cf-card-icon" style="background:var(--green-lt);color:var(--green);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.27h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 17z"/></svg>
            </div>
            <div>
                <div class="cf-card-title">Live Itinerary Details</div>
            </div>
        </div>
        <div class="cf-card-body">
 
            {{-- Booking/Ticket Status --}}
            <div class="cf-row">
                <span class="cf-lbl">Booking Status</span>
                <span class="cf-val normal">
                    <span class="cf-status {{ strtolower($bookingStatus) === 'confirmed' || strtolower($bookingStatus) === 'booked' ? 'confirmed' : 'pending' }}">
                        {{ $bookingStatus }}
                    </span>
                </span>
            </div>
            @if($ticketStatus)
            <div class="cf-row">
                <span class="cf-lbl">Ticket Status</span>
                <span class="cf-val normal">{{ $ticketStatus }}</span>
            </div>
            @endif
 
            {{-- Flight Segments from live API --}}
            @if(!empty($resItems))
            <div style="margin-top:14px;">
                <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--gray-400);margin-bottom:10px;">Flight Segments</div>
                @foreach($resItems as $ri)
                    @php $seg = $ri['ReservationItem'] ?? $ri; @endphp
                    <div style="background:var(--gray-50);border:1px solid var(--gray-200);border-radius:10px;padding:12px 14px;margin-bottom:8px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                            <div style="font-size:14px;font-weight:800;color:var(--navy);">
                                {{ $seg['DepartureAirportLocationCode']??'' }}
                                <span style="font-size:12px;color:var(--gray-400);margin:0 6px;">→</span>
                                {{ $seg['ArrivalAirportLocationCode']??'' }}
                            </div>
                            @if(!empty($seg['AirlinePNR']))
                            <span style="font-size:11px;font-weight:700;color:var(--green);background:var(--green-lt);padding:2px 8px;border-radius:999px;">
                                PNR: {{ $seg['AirlinePNR'] }}
                            </span>
                            @endif
                        </div>
                        <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:12px;color:var(--gray-500);">
                            <span>✈ {{ $seg['MarketingAirlineCode']??'' }}{{ $seg['FlightNumber']??'' }}</span>
                            @if(!empty($seg['DepartureDateTime']))<span>🕐 {{ \Carbon\Carbon::parse($seg['DepartureDateTime'])->format('D, d M Y H:i') }}</span>@endif
                            @if(!empty($seg['ArrivalDateTime']))<span>🛬 {{ \Carbon\Carbon::parse($seg['ArrivalDateTime'])->format('D, d M Y H:i') }}</span>@endif
                            @if(!empty($seg['JourneyDuration']))<span>⏱ {{ floor($seg['JourneyDuration']/60) }}h {{ $seg['JourneyDuration']%60 }}m</span>@endif
                            @if(!empty($seg['CabinClassText']))<span>💺 {{ $seg['CabinClassText'] }}</span>@endif
                            @if(!empty($seg['Baggage']))<span>🧳 {{ $seg['Baggage'] }}</span>@endif
                        </div>
                    </div>
                @endforeach
            </div>
            @endif
 
            {{-- E-Ticket numbers per passenger --}}
            @if(!empty($customerInfos))
            <div style="margin-top:14px;">
                <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--gray-400);margin-bottom:10px;">E-Ticket Numbers</div>
                @foreach($customerInfos as $ci)
                    @php $c = $ci['CustomerInfo'] ?? $ci; @endphp
                    @if(!empty($c['eTicketNumber']))
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--gray-100);font-size:13px;">
                        <span style="color:var(--gray-500)">{{ $c['PassengerTitle']??'' }} {{ $c['PassengerFirstName']??'' }} {{ $c['PassengerLastName']??'' }}</span>
                        <span style="font-family:var(--mono);font-weight:700;color:var(--navy)">{{ $c['eTicketNumber'] }}</span>
                    </div>
                    @endif
                @endforeach
            </div>
            @endif
 
        </div>
    </div>
    @endif
    {{-- ── Flight Summary Card ── --}}
    @if(!empty($firstSeg))
    <div class="cf-card">
        <div class="cf-card-head">
            <div class="cf-card-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.27h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 17z"/></svg>
            </div>
            <div class="cf-card-title">Flight Summary</div>
        </div>
        <div class="cf-card-body">
            <div class="cf-row">
                <span class="cf-lbl">Route</span>
                <span class="cf-val normal" style="font-size:15px;font-weight:800;">
                    {{ $firstSeg['from'] ?? '' }} → {{ $lastSeg['to'] ?? '' }}
                </span>
            </div>
            @if(!empty($firstSeg['airline']))
            <div class="cf-row">
                <span class="cf-lbl">Airline</span>
                <span class="cf-val normal">{{ $firstSeg['airline'] }}</span>
            </div>
            @endif
            @if(!empty($firstSeg['departDate']))
            <div class="cf-row">
                <span class="cf-lbl">Departure</span>
                <span class="cf-val normal">{{ $firstSeg['departDate'] }} at {{ $firstSeg['departTime'] }}</span>
            </div>
            @endif
            @if($totalPrice)
            <div class="cf-row">
                <span class="cf-lbl">Total Paid</span>
                <span class="cf-val" style="font-size:16px;color:var(--navy);">{{ $sym }}{{ number_format($totalPrice, 2) }}</span>
            </div>
            @endif
        </div>
    </div>
    @endif
    @php
        $retSegs   = $flight['returnSegments']  ?? [];
        $multiLegs = $flight['multiLegs']        ?? [];
    @endphp
 
    @if(!empty($retSegs))
    <div class="cf-row">
        <span class="cf-lbl">Return Flight</span>
        <span class="cf-val normal">
            {{ $retSegs[0]['from'] ?? '' }} → {{ $retSegs[count($retSegs)-1]['to'] ?? '' }}
            @if(!empty($flight['returnDateLabel'])) · {{ $flight['returnDateLabel'] }} @endif
        </span>
    </div>
    @endif
 
    @foreach($multiLegs as $li => $leg)
        @php $legSegs = $leg['segments'] ?? []; @endphp
        @if(!empty($legSegs))
        <div class="cf-row">
            <span class="cf-lbl">Leg {{ $li + 2 }}</span>
            <span class="cf-val normal">
                {{ $legSegs[0]['from'] ?? '' }} → {{ $legSegs[count($legSegs)-1]['to'] ?? '' }}
                @if(!empty($leg['departDateLabel'])) · {{ $leg['departDateLabel'] }} @endif
            </span>
        </div>
        @endif
    @endforeach
    {{-- ── Errors ── --}}
    @if(!empty($errorList))
    <div class="cf-card">
        <div class="cf-card-head">
            <div class="cf-card-icon" style="background:var(--red-lt);color:var(--red);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div class="cf-card-title" style="color:var(--red);">Error Details</div>
        </div>
        <div class="cf-card-body">
            <ul class="cf-error-list">
                @foreach($errorList as $err)
                    <li class="cf-error-item">
                        @if($err['code']) <span class="cf-error-code">{{ $err['code'] }}</span> @endif
                        <span>{{ $err['msg'] }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- ── Raw API Response (collapsible) ── --}}
    <div class="cf-card" x-data="{ open: false }">
        <div class="cf-card-head" @click="open = !open" style="cursor:pointer;user-select:none;">
            <div class="cf-card-icon" style="background:#1e293b;color:#94a3b8;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
            </div>
            <div class="cf-card-title">Raw API Response</div>
            <svg style="margin-left:auto;color:var(--gray-400);transition:transform .25s;" :style="open ? 'transform:rotate(180deg)' : ''" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div x-show="open" x-transition>
            <div class="cf-card-body">
                <div class="cf-json-title">BookFlightResponse</div>
                <div class="cf-json">
                    <pre>{{ json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Actions ── --}}
    <div class="cf-actions">
        @if($success)
            <a href="{{ route('home') }}" class="cf-btn-primary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Back to Home
            </a>
            <a href="#" onclick="window.print()" class="cf-btn-ghost">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print / Save
            </a>
        @else
            <a href="{{ route('flights.booking') }}" class="cf-btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Try Again
            </a>
            <a href="{{ route('air.flight-s') }}" class="cf-btn-ghost">Search Again</a>
        @endif
    </div>

</div>

@push('scripts')
<script src="//unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endpush

@endcomponent