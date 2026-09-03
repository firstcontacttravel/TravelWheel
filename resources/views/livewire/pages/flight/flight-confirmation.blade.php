{{-- resources/views/livewire/pages/flight/flight-confirmation.blade.php --}}
@component('layouts.app', ['title' => 'Booking Confirmed'])

@php
    $dbBooking = $dbBooking ?? null;
    $bookingFlight = session('bookingFlight', []);
    $flight = $flight ?? ($bookingFlight['flight'] ?? $bookingFlight);

    if (empty($flight) && $dbBooking) {
        $flight = $dbBooking->flight_snapshot ?? [];
    }

    $currency = $flight['currency'] ?? 'NGN';
    $sym = match ($currency) {
        'NGN' => '₦',
        'USD' => '$',
        'GBP' => '£',
        'EUR' => '€',
        default => $currency . ' ',
    };
    $fmt = fn ($value) => $sym . number_format((float) $value, 2);

    $segments = $flight['segments'] ?? [];
    $returnSegments = $flight['returnSegments'] ?? [];
    $multiLegs = $flight['multiLegs'] ?? [];
    $isReturn = count($returnSegments) > 0;
    $isMulti = count($multiLegs) > 0;
    $tripLabel = $isReturn ? 'Round trip' : ($isMulti ? 'Multi-city' : 'One-way');
    $cabinLabel = \App\Support\FlightDisplay::cabin($flight, $dbBooking);
    $routeLabel = \App\Support\FlightDisplay::route($flight);

    $firstSeg = $segments[0] ?? [];
    $lastSeg = ! empty($segments) ? $segments[count($segments) - 1] : [];
    $finalSeg = $isReturn && ! empty($returnSegments) ? $returnSegments[count($returnSegments) - 1] : $lastSeg;

    $breakdown = $bookingFlight['fareBreakdown'] ?? $flight['fareBreakdown'] ?? [];
    $contact = $contact ?? session('bookingContact', []);
    if (empty($contact) && $dbBooking) {
        $contact = ['email' => $dbBooking->contact_email, 'phone' => $dbBooking->contact_phone];
    }

    $passengers = $passengers ?? session('bookingPassengers', []);
    if (empty($passengers) && $dbBooking) {
        $passengers = $dbBooking->passengers_snapshot ?? [];
    }
    $passengers = \App\Support\FlightDisplay::passengers($passengers);

    $uniqueId = $uniqueId ?? session('bookingUniqueId', $dbBooking?->unique_id ?? '');
    $bookingRef = $bookingRef ?? session('bookingRef', $dbBooking?->booking_ref ?? '');
    $paymentMethod = $paymentMethod ?? session('paymentMethod', $dbBooking?->payment_method ?? 'gateway');
    $tripDetails = $tripDetails ?? [];
    $ticketOrderResult = $ticketOrderResult ?? session('ticketOrderResult', []);

    $bookingStatusText = strtoupper((string) ($tripDetails['BookingStatus'] ?? $bookingStatus ?? session('bookingStatus', $dbBooking?->booking_status ?? 'CONFIRMED')));
    $ticketStatusText = strtoupper((string) ($tripDetails['TicketStatus'] ?? ''));
    $ticketSuccess = (bool) ($ticketSuccess ?? session('ticketSuccess', false));
    $isTicketed = $ticketSuccess || $dbBooking?->isTicketed() || $ticketStatusText === 'TICKETED';
    $isProcessing = ! $isTicketed && in_array($bookingStatusText, ['CONFIRMED', 'BOOKED', 'PAID_UNTICKETED', 'ON_HOLD'], true);

    $baseTotal = (float) ($flight['price'] ?? $dbBooking?->total_price ?? 0);
    $extraServices = $dbBooking?->extra_services_snapshot ?? session('selectedExtras', []);
    $baggageItems = $extraServices['baggage'] ?? [];
    $mealItems = $extraServices['meal'] ?? [];
    $extrasTotal = (float) ($extraServices['total_amount'] ?? 0);
    $grandTotal = $baseTotal + $extrasTotal;

    $reservationItems = collect(data_get($tripDetails, 'ItineraryInfo.ReservationItems', []))->map(fn ($item) => $item['ReservationItem'] ?? $item);
    $customerInfos = collect(data_get($tripDetails, 'ItineraryInfo.CustomerInfos', []))->map(fn ($item) => $item['CustomerInfo'] ?? $item);
    $ticketOrderUniqueId = data_get($ticketOrderResult, 'AirOrderTicketRS.TicketOrderResult.UniqueID', '');

    $pnrMap = [];
    foreach ($reservationItems as $reservationItem) {
        $key = ($reservationItem['MarketingAirlineCode'] ?? '') . ($reservationItem['FlightNumber'] ?? '');
        $pnrMap[$key] = $reservationItem['AirlinePNR'] ?? '';
    }

    $legs = [];
    if ($isMulti) {
        foreach ($multiLegs as $index => $leg) {
            $legs[] = [
                'label' => 'Leg ' . ($index + 1),
                'segments' => $leg['segments'] ?? [],
                'duration' => $leg['totalTimeLabel'] ?? $leg['durationLabel'] ?? '',
                'date' => $leg['departDateLabel'] ?? '',
            ];
        }
    } else {
        $legs[] = [
            'label' => 'Outbound',
            'segments' => $segments,
            'duration' => $flight['totalTimeLabel'] ?? $flight['durationLabel'] ?? '',
            'date' => $flight['departDateLabel'] ?? '',
        ];

        if ($isReturn && ! empty($returnSegments)) {
            $legs[] = [
                'label' => 'Return',
                'segments' => $returnSegments,
                'duration' => $flight['returnTotalTimeLabel'] ?? $flight['returnDurationLabel'] ?? '',
                'date' => $flight['returnDateLabel'] ?? '',
            ];
        }
    }

    $statusTitle = $isTicketed ? 'Booking confirmed and ticketed' : 'Booking confirmed';
    $statusCopy = $isTicketed
        ? 'Your ticket has been issued. A copy of your itinerary has been sent to your email.'
        : 'Your seat is reserved and ticketing is in progress. Your e-ticket will be sent to your email once issued.';
@endphp

<style>
    :root {
        --cf-blue: #303191;
        --cf-blue-700: #252675;
        --cf-green: #009933;
        --cf-soft: #f8f9fc;
        --cf-line: #e6e8ee;
        --cf-line-2: #f2f4f7;
        --cf-text: #111827;
        --cf-muted: #667085;
        --cf-faint: #98a2b3;
        --cf-amber: #d97706;
        --cf-red: #dc2626;
        --cf-shadow: 0 18px 48px rgba(16, 24, 40, .08);
        --cf-font: 'Open Sans', 'Plus Jakarta Sans', Arial, sans-serif;
        --cf-mono: 'DM Mono', Consolas, monospace;
    }

    body {
        margin-top: 112px;
        background: linear-gradient(180deg, #fff 0%, var(--cf-soft) 42%, #fff 100%);
        color: var(--cf-text);
        font-family: var(--cf-font);
    }

    .cf-wrap {
        max-width: 1216px;
        margin: 0 auto;
        padding: 24px 16px 76px;
    }

    .cf-crumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 7px;
        margin-bottom: 16px;
        color: var(--cf-faint);
        font-size: 12px;
    }

    .cf-crumb a {
        color: var(--cf-blue);
        font-weight: 750;
        text-decoration: none;
    }

    .cf-hero {
        position: relative;
        overflow: hidden;
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 18px;
        align-items: center;
        padding: 24px;
        margin-bottom: 18px;
        border: 1px solid rgba(0, 153, 51, .18);
        border-radius: 18px;
        background:
            radial-gradient(circle at 90% 10%, rgba(0, 153, 51, .13), transparent 30%),
            linear-gradient(180deg, #ffffff 0%, #fbfffc 100%);
        box-shadow: var(--cf-shadow);
    }

    .cf-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 28px;
        padding: 5px 11px;
        margin-bottom: 12px;
        border-radius: 999px;
        background: #ecfdf3;
        color: var(--cf-green);
        font-size: 11px;
        font-weight: 900;
    }

    .cf-status.processing {
        background: #fff7ed;
        color: var(--cf-amber);
    }

    .cf-title {
        margin: 0;
        color: var(--cf-text);
        font-size: clamp(26px, 3.5vw, 40px);
        font-weight: 950;
        line-height: 1.08;
        letter-spacing: 0;
    }

    .cf-subtitle {
        max-width: 720px;
        margin-top: 10px;
        color: var(--cf-muted);
        font-size: 14px;
        line-height: 1.65;
    }

    .cf-ref-card {
        min-width: 260px;
        padding: 16px;
        border: 1px solid rgba(48, 49, 145, .14);
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .05);
    }

    .cf-ref-label {
        color: var(--cf-muted);
        font-size: 10.5px;
        font-weight: 900;
        letter-spacing: .05em;
        text-transform: uppercase;
    }

    .cf-ref-value {
        margin-top: 5px;
        color: var(--cf-blue);
        font-family: var(--cf-mono);
        font-size: 22px;
        font-weight: 900;
        word-break: break-word;
    }

    .cf-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 342px;
        gap: 18px;
        align-items: start;
    }

    .cf-main,
    .cf-rail {
        min-width: 0;
    }

    .cf-stack {
        display: grid;
        gap: 12px;
    }

    .cf-card {
        overflow: hidden;
        border: 1px solid var(--cf-line);
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
    }

    .cf-card-head {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 18px;
        border-bottom: 1px solid var(--cf-line-2);
    }

    .cf-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: #f1f1ff;
        color: var(--cf-blue);
        flex: 0 0 36px;
    }

    .cf-card-title {
        color: var(--cf-text);
        font-size: 15px;
        font-weight: 900;
        line-height: 1.25;
    }

    .cf-card-sub {
        margin-top: 2px;
        color: var(--cf-muted);
        font-size: 11.5px;
    }

    .cf-card-body {
        padding: 16px 18px;
    }

    .cf-route-top {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
        gap: 16px;
        align-items: center;
        padding: 18px;
        background: #fbfcfe;
        border-bottom: 1px solid var(--cf-line-2);
    }

    .cf-airport {
        text-align: center;
    }

    .cf-airport-code {
        color: var(--cf-text);
        font-family: var(--cf-mono);
        font-size: clamp(24px, 4vw, 34px);
        font-weight: 950;
        line-height: 1;
    }

    .cf-airport-name {
        margin-top: 5px;
        color: var(--cf-muted);
        font-size: 11.5px;
        line-height: 1.35;
    }

    .cf-plane-line {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--cf-blue);
    }

    .cf-plane-line::before,
    .cf-plane-line::after {
        content: "";
        width: 64px;
        height: 1px;
        background: var(--cf-line);
    }

    .cf-multi-route-top {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 10px;
        padding: 14px 18px;
        background: #fbfcfe;
        border-bottom: 1px solid var(--cf-line-2);
    }

    .cf-multi-route-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        min-width: 0;
        padding: 10px 12px;
        border: 1px solid var(--cf-line);
        border-radius: 12px;
        background: #fff;
    }

    .cf-multi-route-item strong {
        overflow: hidden;
        color: var(--cf-text);
        font-family: var(--cf-mono);
        font-size: 12px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .cf-leg {
        padding: 16px 18px;
        border-top: 1px solid var(--cf-line-2);
    }

    .cf-leg:first-child {
        border-top: 0;
    }

    .cf-leg-head {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        margin-bottom: 12px;
    }

    .cf-leg-title {
        color: var(--cf-text);
        font-size: 14px;
        font-weight: 900;
    }

    .cf-chip {
        display: inline-flex;
        align-items: center;
        min-height: 22px;
        padding: 3px 9px;
        border-radius: 999px;
        background: #eef2ff;
        color: var(--cf-blue);
        font-size: 10.5px;
        font-weight: 900;
        white-space: nowrap;
    }

    .cf-segments {
        display: grid;
        gap: 10px;
    }

    .cf-segment {
        display: grid;
        grid-template-columns: 86px 1fr 120px;
        gap: 12px;
        align-items: center;
        padding: 13px;
        border: 1px solid var(--cf-line);
        border-radius: 14px;
        background: #fff;
    }

    .cf-time {
        color: var(--cf-text);
        font-family: var(--cf-mono);
        font-size: 18px;
        font-weight: 950;
        line-height: 1.1;
    }

    .cf-seg-route {
        display: grid;
        gap: 4px;
        color: var(--cf-text);
        font-size: 13px;
        font-weight: 850;
    }

    .cf-seg-meta {
        color: var(--cf-muted);
        font-size: 11.5px;
        line-height: 1.4;
    }

    .cf-pnr {
        justify-self: end;
        text-align: right;
    }

    .cf-pnr-label {
        color: var(--cf-faint);
        font-size: 10px;
        font-weight: 900;
        text-transform: uppercase;
    }

    .cf-pnr-value {
        margin-top: 3px;
        color: var(--cf-blue);
        font-family: var(--cf-mono);
        font-size: 13px;
        font-weight: 900;
    }

    .cf-alert {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        padding: 14px;
        border: 1px solid #fed7aa;
        border-radius: 14px;
        background: #fff7ed;
        color: #9a3412;
        font-size: 13px;
        line-height: 1.55;
    }

    .cf-ticket-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 10px;
    }

    .cf-ticket {
        padding: 14px;
        border: 1px solid #d7d8ff;
        border-radius: 14px;
        background: #f8f9ff;
    }

    .cf-ticket-name {
        color: var(--cf-text);
        font-size: 12.5px;
        font-weight: 850;
    }

    .cf-ticket-number {
        margin-top: 5px;
        color: var(--cf-blue);
        font-family: var(--cf-mono);
        font-size: 15px;
        font-weight: 900;
    }

    .cf-passengers {
        display: grid;
        gap: 8px;
    }

    .cf-passenger {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 10px;
        align-items: center;
        padding: 12px 13px;
        border: 1px solid var(--cf-line);
        border-radius: 13px;
        background: #fbfcfe;
    }

    .cf-passenger-name {
        color: var(--cf-text);
        font-size: 13px;
        font-weight: 900;
    }

    .cf-passenger-meta {
        margin-top: 3px;
        color: var(--cf-muted);
        font-size: 11.5px;
    }

    .cf-reminders {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .cf-reminder {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        padding: 13px;
        border: 1px solid var(--cf-line);
        border-radius: 13px;
        background: #fbfcfe;
    }

    .cf-reminder svg {
        color: var(--cf-green);
        flex: 0 0 18px;
        margin-top: 1px;
    }

    .cf-reminder-title {
        color: var(--cf-text);
        font-size: 12.5px;
        font-weight: 900;
    }

    .cf-reminder-copy {
        margin-top: 3px;
        color: var(--cf-muted);
        font-size: 11.5px;
        line-height: 1.45;
    }

    .cf-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .cf-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 46px;
        padding: 0 18px;
        border: 1px solid var(--cf-line);
        border-radius: 12px;
        background: #fff;
        color: var(--cf-text);
        font-size: 13.5px;
        font-weight: 900;
        text-decoration: none;
        transition: background .18s ease, border-color .18s ease, transform .18s ease;
    }

    .cf-btn.primary {
        border-color: var(--cf-blue);
        background: var(--cf-blue);
        color: #fff;
        box-shadow: 0 14px 28px rgba(48, 49, 145, .18);
    }

    .cf-btn:hover {
        transform: translateY(-1px);
    }

    .cf-rail {
        position: sticky;
        top: 18px;
        display: grid;
        gap: 12px;
    }

    .cf-summary-head {
        padding: 16px 18px;
        border-bottom: 1px solid var(--cf-line);
        background: linear-gradient(180deg, #fff 0%, #fbfcff 100%);
    }

    .cf-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 8px 0;
        color: var(--cf-muted);
        font-size: 12.5px;
    }

    .cf-row strong {
        color: var(--cf-text);
        font-weight: 900;
        text-align: right;
    }

    .cf-total {
        margin: 12px 14px 14px;
        padding: 16px;
        border: 1px solid rgba(48, 49, 145, .12);
        border-radius: 14px;
        background: #f8f9ff;
    }

    .cf-total-label {
        color: var(--cf-text);
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
    }

    .cf-total-value {
        margin-top: 4px;
        color: var(--cf-blue);
        font-size: 25px;
        font-weight: 950;
        line-height: 1.1;
    }

    @media (max-width: 900px) {
        body {
            margin-top: 0 !important;
        }

        section.navbarmain {
            padding-top: 104px !important;
        }

        main.navbarmain.upper-space {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }

        .cf-wrap {
            padding: 10px 12px 56px;
        }

        .cf-hero {
            grid-template-columns: 1fr;
            padding: 18px;
        }

        .cf-ref-card {
            min-width: 0;
        }

        .cf-grid {
            grid-template-columns: 1fr;
        }

        .cf-rail {
            position: static;
        }

        .cf-reminders {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .cf-route-top {
            grid-template-columns: 1fr;
        }

        .cf-plane-line {
            justify-content: center;
        }

        .cf-plane-line::before,
        .cf-plane-line::after {
            width: 48px;
        }

        .cf-segment {
            grid-template-columns: 1fr;
        }

        .cf-pnr {
            justify-self: start;
            text-align: left;
        }

        .cf-passenger {
            grid-template-columns: 1fr;
        }
    }

    @media print {
        body {
            margin-top: 0;
            background: #fff;
        }

        .cf-crumb,
        .cf-actions,
        header,
        footer,
        .navbarmain {
            display: none !important;
        }

        .cf-grid {
            grid-template-columns: 1fr;
        }

        .cf-rail {
            position: static;
        }

        .cf-card,
        .cf-hero {
            box-shadow: none;
            break-inside: avoid;
        }
    }
</style>

<div class="cf-wrap">
    <nav class="cf-crumb" aria-label="Breadcrumb">
        <a href="{{ route('home') }}">Home</a>
        <span>›</span>
        <a href="{{ route('air.flight-s') }}">Flights</a>
        <span>›</span>
        <span>Confirmation</span>
    </nav>

    @if(isset($errors) && $errors->has('error'))
        <div class="cf-alert" style="margin-bottom:16px;border-color:#fecaca;background:#fef2f2;color:var(--cf-red);">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
            <span>{{ $errors->first('error') }}</span>
        </div>
    @endif

    <section class="cf-hero">
        <div>
            <div class="cf-status {{ $isTicketed ? '' : 'processing' }}">
                @if($isTicketed)
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                    Ticket issued
                @else
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    Ticketing in progress
                @endif
            </div>
            <h1 class="cf-title">{{ $statusTitle }}</h1>
            <p class="cf-subtitle">
                {{ $statusCopy }}
                @if(! empty($contact['email']))
                    Confirmation details are being sent to <strong>{{ $contact['email'] }}</strong>.
                @endif
            </p>
        </div>

        <div class="cf-ref-card">
            <div class="cf-ref-label">Booking reference</div>
            <div class="cf-ref-value">{{ $bookingRef ?: ($uniqueId ?: 'Pending') }}</div>
            <div style="margin-top:8px;color:var(--cf-muted);font-size:11.5px;">{{ ucfirst(str_replace('_', ' ', $paymentMethod)) }}</div>
        </div>
    </section>

    <div class="cf-grid">
        <main class="cf-main cf-stack">
            <section class="cf-card">
                <div class="cf-card-head">
                    <span class="cf-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21 16v-2l-8-5V3.5a1.5 1.5 0 0 0-3 0V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5L21 16Z"/></svg>
                    </span>
                    <div>
                        <div class="cf-card-title">Flight Itinerary</div>
                        <div class="cf-card-sub">{{ $tripLabel }} · {{ $cabinLabel }} · {{ $flight['airline'] ?? 'Selected airline' }}</div>
                    </div>
                </div>

                @if($isMulti)
                    <div class="cf-multi-route-top" aria-label="Multi-city itinerary summary: {{ $routeLabel }}">
                        @foreach($legs as $leg)
                            @php
                                $summarySegments = array_values(array_filter($leg['segments'] ?? []));
                                $summaryFirst = $summarySegments[0] ?? [];
                                $summaryLast = ! empty($summarySegments) ? $summarySegments[count($summarySegments) - 1] : [];
                            @endphp
                            @if(! empty($summarySegments))
                                <div class="cf-multi-route-item">
                                    <span class="cf-chip">{{ $leg['label'] }}</span>
                                    <strong>{{ $summaryFirst['from'] ?? '' }} → {{ $summaryLast['to'] ?? '' }}</strong>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="cf-route-top">
                        <div class="cf-airport">
                            <div class="cf-airport-code">{{ $firstSeg['from'] ?? '' }}</div>
                            <div class="cf-airport-name">{{ $firstSeg['fromCity'] ?? $firstSeg['fromAirport'] ?? 'Departure' }}</div>
                        </div>
                        <div class="cf-plane-line">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21 16v-2l-8-5V3.5a1.5 1.5 0 0 0-3 0V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5L21 16Z"/></svg>
                        </div>
                        <div class="cf-airport">
                            <div class="cf-airport-code">{{ $finalSeg['to'] ?? '' }}</div>
                            <div class="cf-airport-name">{{ $finalSeg['toCity'] ?? $finalSeg['toAirport'] ?? 'Arrival' }}</div>
                        </div>
                    </div>
                @endif

                @foreach($legs as $leg)
                    @php
                        $legSegments = array_values(array_filter($leg['segments'] ?? []));
                        $legFirst = $legSegments[0] ?? [];
                        $legLast = ! empty($legSegments) ? $legSegments[count($legSegments) - 1] : [];
                    @endphp
                    @if(! empty($legSegments))
                        <div class="cf-leg">
                            <div class="cf-leg-head">
                                <div>
                                    <div class="cf-leg-title">{{ $legFirst['from'] ?? '' }} → {{ $legLast['to'] ?? '' }}</div>
                                    <div class="cf-seg-meta">{{ $leg['date'] ?? '' }} @if(! empty($leg['duration'])) · {{ $leg['duration'] }} @endif</div>
                                </div>
                                <span class="cf-chip">{{ $leg['label'] }}</span>
                            </div>
                            <div class="cf-segments">
                                @foreach($legSegments as $segment)
                                    @php
                                        $flightNo = ($segment['airlineCode'] ?? $segment['airline_code'] ?? '') . ($segment['flightNo'] ?? $segment['flight_number'] ?? '');
                                        $pnr = $pnrMap[$flightNo] ?? ($segment['airlinePnr'] ?? '');
                                    @endphp
                                    <div class="cf-segment">
                                        <div>
                                            <div class="cf-time">{{ $segment['departTime'] ?? $segment['departureTime'] ?? '' }}</div>
                                            <div class="cf-seg-meta">{{ $segment['from'] ?? '' }}</div>
                                        </div>
                                        <div class="cf-seg-route">
                                            <span>{{ $segment['fromAirport'] ?? $segment['fromCity'] ?? '' }} → {{ $segment['toAirport'] ?? $segment['toCity'] ?? '' }}</span>
                                            <span class="cf-seg-meta">{{ $segment['airline'] ?? $flight['airline'] ?? '' }} @if(! empty($flightNo)) · {{ $flightNo }} @endif</span>
                                        </div>
                                        <div class="cf-pnr">
                                            <div class="cf-time">{{ $segment['arrivalTime'] ?? $segment['arriveTime'] ?? '' }}</div>
                                            <div class="cf-seg-meta">{{ $segment['to'] ?? '' }}</div>
                                            @if($pnr)
                                                <div class="cf-pnr-label">PNR</div>
                                                <div class="cf-pnr-value">{{ $pnr }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </section>

            @if($isProcessing)
                <div class="cf-alert">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                    <span>Your booking is confirmed and your seat is reserved. Ticketing usually completes within 15 to 30 minutes. Keep this reference handy: <strong>{{ $bookingRef ?: $uniqueId }}</strong>.</span>
                </div>
            @endif

            <section class="cf-card">
                <div class="cf-card-head">
                    <span class="cf-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2h12a2 2 0 0 1 2 2v18l-4-2-4 2-4-2-4 2V4a2 2 0 0 1 2-2Z"/><path d="M8 7h8"/><path d="M8 11h8"/><path d="M8 15h5"/></svg>
                    </span>
                    <div>
                        <div class="cf-card-title">E-ticket Details</div>
                        <div class="cf-card-sub">{{ $isTicketed ? 'Ticket references for your passengers' : 'Ticket numbers will appear after issuance' }}</div>
                    </div>
                </div>
                <div class="cf-card-body">
                    @if($isTicketed && $customerInfos->contains(fn ($item) => ! empty($item['eTicketNumber'])))
                        <div class="cf-ticket-grid">
                            @foreach($customerInfos as $customer)
                                @if(! empty($customer['eTicketNumber']))
                                    <div class="cf-ticket">
                                        <div class="cf-ticket-name">{{ $customer['PassengerTitle'] ?? '' }} {{ $customer['PassengerFirstName'] ?? '' }} {{ $customer['PassengerLastName'] ?? '' }}</div>
                                        <div class="cf-ticket-number">{{ $customer['eTicketNumber'] }}</div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @elseif($isTicketed && $ticketOrderUniqueId)
                        <div class="cf-ticket">
                            <div class="cf-ticket-name">Ticket order reference</div>
                            <div class="cf-ticket-number">{{ $ticketOrderUniqueId }}</div>
                        </div>
                    @else
                        <div style="color:var(--cf-muted);font-size:13px;line-height:1.6;">Your e-ticket is being processed. We will send the ticket number to your email once issued.</div>
                    @endif
                </div>
            </section>

            @if(! empty($passengers))
                <section class="cf-card">
                    <div class="cf-card-head">
                        <span class="cf-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </span>
                        <div>
                            <div class="cf-card-title">Passengers</div>
                            <div class="cf-card-sub">{{ count($passengers) }} passenger{{ count($passengers) === 1 ? '' : 's' }}</div>
                        </div>
                    </div>
                    <div class="cf-card-body">
                        <div class="cf-passengers">
                            @foreach($passengers as $passenger)
                                <div class="cf-passenger">
                                    <div>
                                        <div class="cf-passenger-name">{{ $passenger['title'] ?? '' }} {{ strtoupper($passenger['first_name'] ?? '') }} {{ strtoupper($passenger['last_name'] ?? '') }}</div>
                                        <div class="cf-passenger-meta">
                                            {{ match($passenger['type'] ?? 'ADT') { 'ADT' => 'Adult', 'CHD' => 'Child', 'INF' => 'Infant', default => 'Passenger' } }}
                                            @if(! empty($passenger['dob'])) · DOB {{ \Carbon\Carbon::parse($passenger['dob'])->format('d M Y') }} @endif
                                            @if(! empty($passenger['nationality'])) · {{ $passenger['nationality'] }} @endif
                                        </div>
                                    </div>
                                    @if(! empty($passenger['passport_no']))
                                        <span class="cf-chip">{{ $passenger['passport_no'] }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            <section class="cf-card">
                <div class="cf-card-head">
                    <span class="cf-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    </span>
                    <div>
                        <div class="cf-card-title">Before You Fly</div>
                        <div class="cf-card-sub">A few checks before departure</div>
                    </div>
                </div>
                <div class="cf-card-body">
                    <div class="cf-reminders">
                        <div class="cf-reminder">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                            <div><div class="cf-reminder-title">Arrive early</div><div class="cf-reminder-copy">Arrive at least 2 hours before domestic flights and 3 hours before international flights.</div></div>
                        </div>
                        <div class="cf-reminder">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h10"/><path d="M7 12h5"/><path d="M7 16h7"/></svg>
                            <div><div class="cf-reminder-title">Bring valid ID</div><div class="cf-reminder-copy">Names on your ID or passport must match the ticket details exactly.</div></div>
                        </div>
                        <div class="cf-reminder">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            <div><div class="cf-reminder-title">Check baggage</div><div class="cf-reminder-copy">Review baggage allowance before travel. Excess baggage is paid at the airport.</div></div>
                        </div>
                        <div class="cf-reminder">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/></svg>
                            <div><div class="cf-reminder-title">Online check-in</div><div class="cf-reminder-copy">Most airlines open online check-in 24 to 48 hours before departure.</div></div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="cf-actions">
                <a class="cf-btn primary" href="{{ route('home') }}">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/><path d="M9 22V12h6v10"/></svg>
                    Back to Home
                </a>
                <button class="cf-btn" type="button" onclick="window.print()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                    Print Itinerary
                </button>
            </div>
        </main>

        <aside class="cf-rail">
            <section class="cf-card">
                <div class="cf-summary-head">
                    <div class="cf-card-title">Fare Summary</div>
                    <div class="cf-card-sub">{{ $tripLabel }} · {{ count($passengers) }} passenger{{ count($passengers) === 1 ? '' : 's' }}</div>
                </div>
                <div class="cf-card-body" style="padding-top:10px;padding-bottom:8px;">
                    @forelse($breakdown as $fare)
                        @php
                            $typeLabel = match($fare['passengerType'] ?? 'ADT') { 'ADT' => 'Adult', 'CHD' => 'Child', 'INF' => 'Infant', default => 'Passenger' };
                            $qty = (int) ($fare['qty'] ?? 1);
                        @endphp
                        <div class="cf-row"><span>{{ $typeLabel }} × {{ $qty }}</span><strong>{{ $fmt(((float) ($fare['totalFare'] ?? 0)) * $qty) }}</strong></div>
                    @empty
                        <div class="cf-row"><span>Flight fare</span><strong>{{ $fmt($baseTotal) }}</strong></div>
                    @endforelse

                    @if($extrasTotal > 0)
                        <div class="cf-row"><span>Extras</span><strong>{{ $fmt($extrasTotal) }}</strong></div>
                    @endif
                </div>
                <div class="cf-total">
                    <div class="cf-total-label">Total Paid</div>
                    <div class="cf-total-value">{{ $fmt($grandTotal) }}</div>
                </div>
            </section>

            <section class="cf-card">
                <div class="cf-card-head">
                    <span class="cf-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16v16H4z"/><path d="m22 6-10 7L2 6"/></svg>
                    </span>
                    <div>
                        <div class="cf-card-title">Contact Details</div>
                        <div class="cf-card-sub">Used for ticket delivery</div>
                    </div>
                </div>
                <div class="cf-card-body" style="padding-top:10px;">
                    <div class="cf-row"><span>Email</span><strong style="font-size:12px;">{{ $contact['email'] ?? '-' }}</strong></div>
                    <div class="cf-row"><span>Phone</span><strong>{{ $contact['phone_full'] ?? $contact['phone'] ?? '-' }}</strong></div>
                    <div class="cf-row"><span>Payment</span><strong>{{ ucfirst(str_replace('_', ' ', $paymentMethod)) }}</strong></div>
                    @if($uniqueId)
                        <div class="cf-row"><span>Ticket ref</span><strong style="font-family:var(--cf-mono);font-size:11px;">{{ $uniqueId }}</strong></div>
                    @endif
                </div>
            </section>

            <section class="cf-card">
                <div class="cf-card-body">
                    <div class="cf-card-title">Need Help?</div>
                    <div style="margin-top:8px;color:var(--cf-muted);font-size:12.5px;line-height:1.65;">
                        Contact TravelWheel support with your booking reference if your e-ticket does not arrive within the expected time.
                    </div>
                    <div style="margin-top:12px;color:var(--cf-blue);font-weight:900;font-family:var(--cf-mono);">{{ $bookingRef ?: $uniqueId }}</div>
                </div>
            </section>
        </aside>
    </div>
</div>
@endcomponent
