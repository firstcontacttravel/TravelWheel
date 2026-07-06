@component('layouts.app', ['title' => 'Protocol Payment - TravelWheel'])
@include('air.protocol.partials.protocol-ui')

@php
    $package = $protocols->package == '2' ? 'Regular' : 'VIP';
    $formattedValue = number_format($protocols->amount);
    $trans_id = $protocols->trans_id;
@endphp

<section class="protocol-page">
    <div class="protocol-wrap">
        <div class="protocol-steps">
            <span class="protocol-step"><x-ph-icon name="map-pin" /> Select airport</span>
            <span class="protocol-step"><x-ph-icon name="tag" /> Choose plan</span>
            <span class="protocol-step"><x-ph-icon name="identification-card" /> Add details</span>
            <span class="protocol-step protocol-step-active"><x-ph-icon name="check-circle" /> Confirmed</span>
        </div>

        <div class="protocol-payment-grid">
            <div class="protocol-success" style="max-width:none;">
                <div class="protocol-success-icon "><x-ph-icon name="check" /></div><br>
                <div class="protocol-kicker justify-content-center"><x-ph-icon name="shield-check" /> Payment Successful</div>
                <h1 class="protocol-title">Your protocol service is confirmed</h1>
                <p class="protocol-copy">
                    The sum of ₦{{ $formattedValue }} has been received for Protocol Service ({{ $package }}).
                </p>
                <a href="{{ route('air.protocol_generate', ['trans_id' => $trans_id]) }}" class="protocol-btn mt-4" id="generate-pass-link">
                    Generate Pass <x-ph-icon name="download-simple" />
                </a>
            </div>

            <div class="protocol-panel">
                <div class="protocol-section-title"><x-ph-icon name="list-magnifying-glass" /> Service Details</div>
                <div class="protocol-detail-grid" style="grid-template-rows:2fr;">
                    <div class="protocol-detail"><span>Location</span><strong>{{ $protocols->state }}</strong></div>
                    <div class="protocol-detail"><span>Airport</span><strong>{{ $protocols->airport }}</strong></div>
                    <div class="protocol-detail"><span>Travel Date</span><strong>{{ $protocols->travel_date?->format('d M Y') }}</strong></div>
                    <div class="protocol-detail"><span>{{ $protocols->service_type }} Time</span><strong>{{ $protocols->d_time }}</strong></div>
                    <div class="protocol-detail"><span>Airline</span><strong>{{ $protocols->airline }}</strong></div>
                    <div class="protocol-detail"><span>Passengers</span><strong>{{ $protocols->passenger }}</strong></div>
                    <div class="protocol-detail"><span>Phone</span><strong>{{ $protocols->phone }}</strong></div>
                    <div class="protocol-detail"><span>Email</span><strong>{{ $protocols->email }}</strong></div>
                    <div class="protocol-detail"><span>Optional Request</span><strong>{{ $protocols->optional_request }}</strong></div>
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
