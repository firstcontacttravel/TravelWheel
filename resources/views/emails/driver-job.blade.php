@php
    $row = fn (string $label, $value) => filled($value)
        ? '<tr><td style="padding:8px 0; border-bottom:1px solid #eaecf8; font-size:13px; color:#888; width:42%;">'.e($label).'</td><td style="padding:8px 0; border-bottom:1px solid #eaecf8; font-size:13px; color:#1a1a1a; font-weight:600; text-align:right;">'.e($value).'</td></tr>'
        : '';
    $date = $booking?->pickup_date ? \Carbon\Carbon::parse($booking->pickup_date)->format('D, j M Y') : null;
    $time = $booking?->pickup_time ? \Carbon\Carbon::parse($booking->pickup_time)->format('g:i A') : null;
    $hours = (float) ($booking->rental_hours ?? 0);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Trip Assigned</title>
</head>
<body style="margin:0; padding:0; background:#f0f2f8; font-family: 'DM Sans', Arial, sans-serif; color:#1a1a1a;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f2f8; padding:30px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.06);">
                    <tr>
                        <td style="background:linear-gradient(135deg,#0d1883,#2d39b6); padding:28px 30px;">
                            <p style="margin:0; font-size:20px; font-weight:700; color:#fff;">TravelWheel</p>
                            <p style="margin:4px 0 0; font-size:12px; color:rgba(255,255,255,0.75); letter-spacing:0.05em; text-transform:uppercase;">Travel Connections · Driver Job</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px;">
                            <h2 style="margin:0 0 8px; font-size:20px; color:#0d1883;">You have a new trip 🚗</h2>
                            <p style="margin:0 0 24px; font-size:14px; color:#555; line-height:1.6;">
                                Hi {{ $driver->name ?? 'there' }}, you've been assigned to the {{ $isTransfer ? 'pick-up & drop-off' : 'car hire' }} booking below. Please be at the pick-up point on time.
                            </p>

                            {{-- When & where --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#eef1ff; border:1px solid #c5cef8; border-radius:10px; margin-bottom:20px;">
                                <tr>
                                    <td style="padding:18px 20px;">
                                        <p style="margin:0 0 4px; font-size:11px; font-weight:700; color:#0d1883; text-transform:uppercase; letter-spacing:0.05em;">Pick-up</p>
                                        <p style="margin:0 0 14px; font-size:16px; font-weight:600; color:#1a1a1a;">{{ $date ?? '—' }}@if ($time) · {{ $time }}@endif</p>
                                        <p style="margin:0 0 4px; font-size:11px; font-weight:700; color:#0d1883; text-transform:uppercase; letter-spacing:0.05em;">Location</p>
                                        <p style="margin:0; font-size:14px; color:#1a1a1a;">{{ $booking->pickup_location ?? '—' }}</p>
                                        @if ($isTransfer && filled($booking->dropoff_location ?? null))
                                            <p style="margin:14px 0 4px; font-size:11px; font-weight:700; color:#0d1883; text-transform:uppercase; letter-spacing:0.05em;">Drop-off</p>
                                            <p style="margin:0; font-size:14px; color:#1a1a1a;">{{ $booking->dropoff_location }}</p>
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            {{-- Customer & trip --}}
                            <p style="margin:0 0 8px; font-size:11px; font-weight:700; color:#0d1883; text-transform:uppercase; letter-spacing:0.05em;">Customer &amp; trip</p>
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
                                {!! $row('Customer', $booking->full_name ?? null) !!}
                                {!! $row('Customer phone', $booking->phone_number ?? null) !!}
                                {!! $row('Passengers', $booking->passengers ?? null) !!}
                                @if ($isTransfer)
                                    {!! $row('Distance', ($booking->distance_km ?? null) ? rtrim(rtrim(number_format((float) $booking->distance_km, 1), '0'), '.').' km' : null) !!}
                                    {!! $row('Flight / vessel no.', $booking->flight_number ?? null) !!}
                                    {!! $row('Special requests', $booking->special_requests ?? null) !!}
                                @else
                                    {!! $row('Duration', $hours ? rtrim(rtrim(number_format($hours, 1), '0'), '.').' hour'.($hours == 1 ? '' : 's') : null) !!}
                                @endif
                                {!! $row('Booking reference', $booking->payment_reference ?? null) !!}
                            </table>

                            {{-- Car --}}
                            <p style="margin:0 0 8px; font-size:11px; font-weight:700; color:#0d1883; text-transform:uppercase; letter-spacing:0.05em;">Car to bring</p>
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
                                {!! $row('Vehicle', $assignment->car_model) !!}
                                {!! $row('Colour', $assignment->car_colour) !!}
                                {!! $row('Plate number', $assignment->plate_number) !!}
                            </table>

                            <p style="margin:24px 0 0; font-size:13px; color:#888; line-height:1.6;">
                                If you can't make this trip, contact the TravelWheel operations team immediately at reservation@travelwheel.ng.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f7f8ff; padding:18px 30px; text-align:center; border-top:1px solid #e8eaf5;">
                            <p style="margin:0; font-size:11px; color:#999;">© {{ date('Y') }} TravelWheel. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
