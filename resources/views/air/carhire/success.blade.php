@component('layouts.app', ['title' => 'Booking Confirmed - TravelWheel'])

@php
    $isTransfer = ($productType ?? 'car_hire') === 'transfer';
    $typeName = function (?string $type): string {
        return $type === 'van' ? 'Mini Van' : ucfirst((string) $type);
    };
    $fmtDate = function ($date): string {
        try { return \Illuminate\Support\Carbon::parse($date)->format('D, j M Y'); } catch (\Throwable) { return (string) $date; }
    };
    $fmtTime = function ($time): string {
        try { return \Illuminate\Support\Carbon::parse($time)->format('g:i A'); } catch (\Throwable) { return (string) $time; }
    };
@endphp

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
<style>
    .chs-root { font-family: 'DM Sans', sans-serif; background: #f0f2f8; padding: 48px 16px 64px; color: #1a1a1a; }
    .chs-card { max-width: 620px; margin: 0 auto; background: #fff; border-radius: 18px; box-shadow: 0 8px 30px rgba(13,24,131,.08); overflow: hidden; }
    .chs-head { text-align: center; padding: 36px 28px 26px; border-bottom: 1px solid #eceef6; }
    .chs-icon { width: 64px; height: 64px; border-radius: 50%; background: #e8f7ee; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px; }
    .chs-icon svg { width: 32px; height: 32px; fill: #1a8a4a; }
    .chs-head h1 { font-family: 'Playfair Display', serif; font-size: 26px; font-weight: 600; color: #0d1883; margin: 0 0 8px; }
    .chs-head p { font-size: 14px; color: #555; margin: 0 auto; max-width: 440px; line-height: 1.55; }
    .chs-ref { display: inline-flex; align-items: center; gap: 8px; margin-top: 18px; background: #eef1ff; border-radius: 10px; padding: 8px 14px; font-size: 12px; color: #555; }
    .chs-ref strong { color: #0d1883; font-size: 14px; letter-spacing: .04em; }
    .chs-ref button { border: none; background: none; color: #0d1883; font-size: 11px; font-weight: 600; cursor: pointer; padding: 0 0 0 6px; border-left: 1px solid #c5cef8; }
    .chs-body { padding: 24px 28px 8px; }
    .chs-label { font-size: 10px; font-weight: 700; color: #999; text-transform: uppercase; letter-spacing: .09em; margin-bottom: 10px; }
    .chs-rows { margin-bottom: 22px; }
    .chs-row { display: flex; justify-content: space-between; gap: 16px; padding: 8px 0; font-size: 13px; border-bottom: 1px dashed #eceef6; }
    .chs-row:last-child { border-bottom: none; }
    .chs-row span { color: #777; flex-shrink: 0; }
    .chs-row strong { color: #1a1a1a; font-weight: 600; text-align: right; }
    .chs-total { display: flex; justify-content: space-between; align-items: center; background: #f6f7fc; border-radius: 12px; padding: 14px 16px; margin-bottom: 24px; }
    .chs-total span { font-size: 13px; color: #555; }
    .chs-total span small { display: block; font-size: 11px; color: #999; }
    .chs-total strong { font-size: 20px; color: #0d1883; }
    .chs-steps { list-style: none; padding: 0; margin: 0 0 24px; display: flex; flex-direction: column; gap: 12px; }
    .chs-steps li { display: flex; gap: 12px; font-size: 13px; color: #444; line-height: 1.5; }
    .chs-steps li b { flex-shrink: 0; width: 22px; height: 22px; border-radius: 50%; background: #0d1883; color: #fff; font-size: 11px; display: inline-flex; align-items: center; justify-content: center; margin-top: 1px; }
    .chs-actions { display: flex; gap: 10px; padding: 0 28px 28px; flex-wrap: wrap; }
    .chs-btn { flex: 1 1 180px; text-align: center; padding: 12px 16px; border-radius: 10px; font-size: 13px; font-weight: 600; text-decoration: none; transition: all .2s; }
    .chs-btn-primary { background: #0d1883; color: #fff; }
    .chs-btn-primary:hover { background: #091264; color: #fff; }
    .chs-btn-ghost { background: #f0f3ff; color: #0d1883; border: 1.5px solid #c5cef8; }
    .chs-btn-ghost:hover { background: #e0e8ff; color: #0d1883; }
    /* The fixed top bar + navbar cover the top of the page above 650px (the
       layout only pads for them below that) — same offset as the lounge pages. */
    @media (min-width: 651px) {
        .chs-root { margin-top: 100px; }
    }
    @media (max-width: 480px) {
        .chs-root { padding: 24px 12px 40px; }
        .chs-head { padding: 28px 18px 22px; }
        .chs-body { padding: 20px 18px 4px; }
        .chs-actions { padding: 0 18px 22px; }
    }
</style>

<section class="chs-root">
    <div class="chs-card">
        <div class="chs-head">
            <div class="chs-icon"><svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg></div>
            <h1>Booking confirmed</h1>
            @if ($booking)
                <p>
                    Thank you, {{ \Illuminate\Support\Str::of($booking->full_name)->before(' ') }}. We've received your payment and your
                    {{ $isTransfer ? 'pick-up & drop-off' : 'car hire' }} is booked.
                </p>
                <div class="chs-ref">
                    Booking ref <strong id="chsRef">{{ $booking->payment_reference }}</strong>
                    <button type="button" onclick="navigator.clipboard?.writeText(document.getElementById('chsRef').textContent).then(() => { this.textContent = 'Copied'; })">Copy</button>
                </div>
            @else
                <p>We've received your payment and your booking is confirmed. A confirmation email with your booking details is on its way.</p>
            @endif
        </div>

        @if ($booking)
            <div class="chs-body">
                <div class="chs-label">{{ $isTransfer ? 'Your transfer' : 'Your car hire' }}</div>
                <div class="chs-rows">
                    @if ($isTransfer)
                        <div class="chs-row"><span>Vehicle</span><strong>{{ filled($booking->category) ? $booking->category.' '.$typeName($booking->vehicle_type) : $booking->vehicle_name }}</strong></div>
                        <div class="chs-row"><span>Pick-up</span><strong>{{ $booking->pickup_location }}</strong></div>
                        <div class="chs-row"><span>Drop-off</span><strong>{{ $booking->dropoff_location }}</strong></div>
                        @if ($booking->distance_km)
                            <div class="chs-row"><span>Distance</span><strong>{{ rtrim(rtrim(number_format($booking->distance_km, 1), '0'), '.') }} km</strong></div>
                        @endif
                        @if ($booking->flight_number)
                            <div class="chs-row"><span>Flight number</span><strong>{{ $booking->flight_number }}</strong></div>
                        @endif
                    @else
                        <div class="chs-row"><span>Vehicle</span><strong>{{ $booking->category }} {{ $typeName($booking->car_type) }}</strong></div>
                        <div class="chs-row"><span>Pick-up location</span><strong>{{ $booking->pickup_location }}</strong></div>
                        @if ($booking->rental_hours)
                            @php $hrs = (float) $booking->rental_hours; @endphp
                            <div class="chs-row"><span>Duration</span><strong>{{ rtrim(rtrim(number_format($hrs, 1), '0'), '.') }} hour{{ $hrs == 1 ? '' : 's' }}</strong></div>
                        @endif
                    @endif
                    <div class="chs-row"><span>Date &amp; time</span><strong>{{ $fmtDate($booking->pickup_date) }} · {{ $fmtTime($booking->pickup_time) }}</strong></div>
                    <div class="chs-row"><span>Passengers</span><strong>{{ $booking->passengers }}</strong></div>
                </div>

                <div class="chs-total">
                    <span>Amount paid<small>via {{ $booking->payment_option === 'budpay' ? 'BudPay' : 'SeerBit' }}</small></span>
                    <strong>&#8358;{{ number_format($booking->amount) }}</strong>
                </div>

                <div class="chs-label">What happens next</div>
                <ol class="chs-steps">
                    <li><b>1</b><span>A confirmation email has been sent to <strong>{{ $booking->email }}</strong>. If you can't find it, check your spam or junk folder.</span></li>
                    <li><b>2</b><span>Before your pick-up, we'll email you your driver's name and phone number, plus the car's model, colour and plate number.</span></li>
                    <li><b>3</b><span>Need to cancel? It's free up to 5 hours before your trip.</span></li>
                </ol>
            </div>
        @endif

        <div class="chs-actions">
            <a href="{{ route('air.carhire') }}" class="chs-btn chs-btn-primary">Book another ride</a>
            <a href="{{ route('home') }}" class="chs-btn chs-btn-ghost">Back to home</a>
        </div>
    </div>
</section>
@endcomponent
