{{-- resources/views/emails/booking-confirmed.blade.php --}}
{{-- Email-safe: tables + inline styles only --}}
@php
    $passengers  = \App\Support\FlightDisplay::passengers($booking->passengers_snapshot ?? []);
    $cabinLabel  = \App\Support\FlightDisplay::cabin($booking->flight_snapshot ?? [], $booking);
    $firstPax    = collect($passengers)->first();
    $firstName   = $firstPax['first_name'] ?? 'Traveller';

    // Build e-ticket number map from live tripDetails (ItemRPH → eTicketNumber)
    $eticketMap = collect(data_get($tripDetails, 'ItineraryInfo.CustomerInfos', []))
        ->map(fn($c) => $c['CustomerInfo'] ?? $c)
        ->pluck('eTicketNumber', 'ItemRPH')
        ->filter()
        ->toArray();

    $hasEtickets = !empty($eticketMap);

    $currency = $booking->currency ?? 'NGN';
    $sym      = match($currency) { 'NGN' => '₦', 'USD' => '$', 'GBP' => '£', 'EUR' => '€', default => $currency . ' ' };
    $price    = $sym . number_format((float)($booking->total_price ?? 0), 2);

    // ── Extra Services ────────────────────────────────────────────────────────
    $extraServices = $booking->extra_services_snapshot ?? [];
    $baggageItems  = $extraServices['baggage'] ?? [];
    $mealItems     = $extraServices['meal'] ?? [];
    $extrasTotal   = $extraServices['total_amount'] ?? 0;
    $extrasCurrency = $extraServices['currency'] ?? 'USD';
    $extrasSym = match($extrasCurrency) { 'NGN' => '₦', 'USD' => '$', 'GBP' => '£', 'EUR' => '€', default => $extrasCurrency . ' ' };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking Confirmed – {{ $booking->booking_ref }}</title>
<style>
    body{margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Arial,sans-serif;font-size:14px;color:#0f172a}
    .wrap{max-width:600px;margin:32px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.1)}
    .header{background:linear-gradient(135deg,#064e3b,#059669);padding:28px 32px;text-align:center;color:#fff}
    .header-icon{font-size:48px;margin-bottom:8px}
    .header-title{font-size:22px;font-weight:800;margin-bottom:4px}
    .header-sub{font-size:13px;opacity:.85}
    .body{padding:28px 32px}
    .ref-box{background:#f0fdf4;border:1px solid #a7f3d0;border-radius:10px;padding:16px 20px;margin-bottom:22px;text-align:center}
    .ref-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#065f46;margin-bottom:4px}
    .ref-value{font-size:24px;font-weight:800;color:#064e3b;font-family:'Courier New',monospace;letter-spacing:.06em}
    .ref-sub{font-size:12px;color:#059669;margin-top:4px;font-weight:600}
    .section-title{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#94a3b8;margin-bottom:12px;margin-top:22px}
    table.detail{width:100%;border-collapse:collapse;font-size:13px}
    table.detail td{padding:8px 0;border-bottom:1px solid #f1f5f9;vertical-align:top}
    table.detail td:first-child{color:#64748b;width:40%}
    table.detail td:last-child{font-weight:700;text-align:right}
    .pax-table{width:100%;border-collapse:collapse;font-size:12.5px;margin-top:4px;border:1px solid #e2e8f0}
    .pax-table th{background:#f8fafc;padding:8px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#94a3b8;border-bottom:1px solid #e2e8f0}
    .pax-table td{padding:10px 12px;border-bottom:1px solid #f1f5f9;color:#334155}
    .pax-table tr:last-child td{border-bottom:none}
    .total-row{padding:14px 18px;background:#f0fdf4;border-radius:10px;margin-top:18px}
    .pdf-notice{background:#eef2ff;border:1px solid #c7d2fe;border-radius:8px;padding:12px 16px;margin-bottom:18px;font-size:12px;color:#4338ca}
    .footer{background:#f8fafc;padding:20px 32px;text-align:center;border-top:1px solid #f1f5f9;font-size:12px;color:#94a3b8}
    .footer a{color:#1d4ed8;text-decoration:none}
</style>
</head>
<body>
<div class="wrap">

    <div class="header">
        <div class="header-icon">✅</div>
        <div class="header-title">Booking Confirmed!</div>
        <div class="header-sub">Your e-ticket is ready. Have a great flight! ✈️</div>
    </div>

    <div class="body">
        <p style="font-size:14px;line-height:1.7;margin-bottom:20px;color:#334155">
            Hi <strong>{{ $firstName }}</strong>,<br><br>
            Great news! Your booking is <strong>confirmed</strong> and your e-ticket has been issued.
            @if($hasEtickets)
                A copy of your E-ticket is attached in PDF to this email — please keep it safe and present it at the check-in counter along with your valid Passport and Visa.
            @else
                Your ticket details are below. If a PDF attachment is not visible,
                please contact us and we will resend it.
            @endif
        </p>

        {{-- PDF notice --}}
        @if($hasEtickets)
        <div class="pdf-notice">
            🎫 <strong>E-Ticket PDF attached</strong> — Print or save to your phone for check-in.
        </div>
        @endif

        {{-- Booking reference --}}
        <div class="ref-box">
            <div class="ref-label">Booking Reference</div>
            <div class="ref-value">{{ $booking->booking_ref }}</div>
            <div class="ref-sub">✓ Ticket Issued</div>
        </div>

        {{-- Flight details --}}
        <div class="section-title">Flight Details</div>
        <table class="detail">
            <tr><td>Route</td><td>{{ $booking->route }}</td></tr>
            <tr><td>Airline</td><td>{{ $booking->airline }}</td></tr>
            <tr><td>Cabin</td><td>{{ $cabinLabel }}</td></tr>
            <tr><td>Fare Type</td><td>{{ $booking->fare_type }}</td></tr>
            <tr><td>Payment Method</td><td>{{ ucfirst(str_replace('_', ' ', $booking->payment_method ?? '')) }}</td></tr>
        </table>

        {{-- Passengers --}}
        @if(!empty($passengers))
        <div class="section-title">Passengers</div>
        <table class="pax-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>DOB</th>
                    <th>Passport</th>
                    @if($hasEtickets)<th>E-Ticket #</th>@endif
                </tr>
            </thead>
            <tbody>
                @foreach($passengers as $i => $pax)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td><strong>{{ $pax['title'] ?? '' }} {{ strtoupper($pax['first_name'] ?? '') }} {{ strtoupper($pax['last_name'] ?? '') }}</strong></td>
                    <td>{{ match($pax['type'] ?? 'ADT') { 'ADT' => 'Adult', 'CHD' => 'Child', 'INF' => 'Infant', default => 'Pax' } }}</td>
                    <td>{{ !empty($pax['dob']) ? \Carbon\Carbon::parse($pax['dob'])->format('d M Y') : '—' }}</td>
                    <td>{{ $pax['passport_no'] ?? '—' }}</td>
                    @if($hasEtickets)
                        <td style="font-family:'Courier New',monospace;font-weight:700;color:#059669">
                            {{ $eticketMap[$i + 1] ?? '—' }}
                        </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- ── Extra Services (if any) ── --}}
        @if(!empty($baggageItems) || !empty($mealItems))
        <div class="section-title">Extra Services</div>
        <table class="detail">
            @foreach($baggageItems as $bag)
            <tr>
                <td>🧳 {{ $bag['description'] }}</td>
                <td>{{ $extrasSym }}{{ number_format($bag['line_total'], 2) }}</td>
            </tr>
            @endforeach
            @foreach($mealItems as $meal)
            <tr>
                <td>🍽️ {{ $meal['description'] }} (Seg {{ $meal['segment'] + 1 }})</td>
                <td>{{ $extrasSym }}{{ number_format($meal['unit_price'], 2) }}</td>
            </tr>
            @endforeach
        </table>
        @endif

        {{-- Total --}}
        <div class="total-row" style="display:table;width:100%;box-sizing:border-box;">
            <span style="font-size:14px;font-weight:800;color:#064e3b;display:table-cell;">Total Paid</span>
            <span style="font-size:20px;font-weight:800;color:#064e3b;font-family:'Courier New',monospace;display:table-cell;text-align:right;">{{ $price }}</span>
        </div>

        <p style="font-size:13px;color:#64748b;line-height:1.7;margin-top:22px">
            <strong>Important reminders:</strong><br>
            Arrive at least 2 hrs before domestic / 3 hrs before international flights.<br>
            Carry a valid passport, with at least 6 Months to expire. Names must match your ticket exactly.<br>
            Check your airline's baggage policy before departure.<br>
            Online check-in typically opens 24–48 hrs before departure.<br><br>
            <strong>Need help?</strong> Email
            <a href="mailto:support@travelwheel.com">support@travelwheel.com</a>
            or call +234 800 000 0000 (Mon–Fri 8am–6pm).<br>
            Always quote your booking reference: <strong>{{ $booking->booking_ref }}</strong>
        </p>
    </div>

    <div class="footer">
        &copy; {{ date('Y') }} TravelWheel. All rights reserved.<br>
        <a href="#">Privacy Policy</a> &middot; <a href="#">Terms of Service</a>
    </div>

</div>
</body>
</html>
