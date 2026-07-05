@component('layouts.app', ['title' => 'Protocol Booking Successful - TravelWheel'])
@include('air.protocol.partials.protocol-ui')

<section class="protocol-page">
    <div class="protocol-wrap">
        <div class="protocol-success">
            <div class="protocol-success-icon"><x-ph-icon name="check" /></div>
            <div class="protocol-kicker justify-content-center"><x-ph-icon name="shield-check" /> Booking Successful</div>
            <h1 class="protocol-title">Protocol service booked</h1>
            <p class="protocol-copy">
                Your booking has been completed. Please check your inbox, spam, or junk folder for the email address provided.
            </p>

            @if(session('message'))
                <div class="alert alert-info mt-3">{{ session('message') }}</div>
            @endif

            <a href="{{ route('air.protocol') }}" class="protocol-btn mt-4">
                Book Another Protocol Service <x-ph-icon name="arrow-right" />
            </a>
        </div>
    </div>
</section>
@endcomponent
