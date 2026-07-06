@component('layouts.app', ['title' => 'Lounge Payment - TravelWheel'])
@include('air.lounge.partials.lounge-ui')

<section class="lounge-page">
    <div class="lounge-wrap">
        <div class="lounge-steps">
            <span class="lounge-step"><x-ph-icon name="map-pin" /> {{ $booking->lounge_name }}</span>
            <span class="lounge-step"><x-ph-icon name="receipt" /> Checkout</span>
            <span class="lounge-step lounge-step-active"><x-ph-icon name="check-circle" /> Confirmed</span>
        </div>

        <div class="lounge-payment-grid">
            <div class="lounge-success" style="max-width:none;">
                <div class="lounge-success-icon"><x-ph-icon name="check" /></div><br>
                <div class="lounge-kicker justify-content-center"><x-ph-icon name="shield-check" /> Payment Successful</div>
                <h1 class="lounge-title">Your lounge booking is confirmed</h1>
                <p class="lounge-copy">
                    The sum of ₦{{ number_format($booking->amount) }} has been received for Lounge Booking ({{ $booking->lounge_name }}).
                </p>
                <a href="{{ route('air.lounge_generate', ['trans_id' => $trans_id]) }}" class="lounge-btn mt-4" id="generate-pass-link">
                    Generate Pass <x-ph-icon name="download-simple" />
                </a>
            </div>

            <div class="lounge-panel">
                <div class="lounge-section-title"><x-ph-icon name="list-magnifying-glass" /> Booking Summary</div>
                <div class="lounge-detail-grid" style="grid-template-rows:2fr;">
                    <div class="lounge-detail"><span>Lounge</span><strong>{{ $booking->lounge_name }}</strong></div>
                    <div class="lounge-detail"><span>Travel Date</span><strong>{{ $booking->travel_date?->format('d M Y') }}</strong></div>
                    <div class="lounge-detail"><span>Time</span><strong>{{ $booking->d_time }}</strong></div>
                    <div class="lounge-detail"><span>Airline</span><strong>{{ $booking->airline }}</strong></div>
                    <div class="lounge-detail"><span>Passengers</span><strong>Adult ({{ $booking->noa }}) Child ({{ $booking->noc }}) Infant ({{ $booking->noi }})</strong></div>
                    <div class="lounge-detail"><span>Phone</span><strong>{{ $booking->phone_no }}</strong></div>
                    <div class="lounge-detail"><span>Email</span><strong>{{ $booking->email }}</strong></div>
                    <div class="lounge-detail"><span>Transaction Reference</span><strong style="font-size:12px;">{{ $booking->trans_id }}</strong></div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.getElementById('generate-pass-link').addEventListener('click', function () {
        this.textContent = 'Generating...';
        this.classList.add('disabled');
    });
</script>
@endcomponent
