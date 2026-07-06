@component('layouts.app', ['title' => 'Lounge Booking Successful - TravelWheel'])
@include('air.lounge.partials.lounge-ui')

<section class="lounge-page">
    <div class="lounge-wrap">
        <div class="lounge-success">
            <div class="lounge-success-icon"><x-ph-icon name="check" /></div>
            <div class="lounge-kicker justify-content-center"><x-ph-icon name="shield-check" /> Booking Successful</div>
            <h1 class="lounge-title">Lounge service booked</h1>
            <p class="lounge-copy">
                Your booking has been completed. Please check your inbox, spam, or junk folder for the email address provided — your Lounge Pass will be sent there.
            </p>

            @if(session('message'))
                <div class="alert alert-info mt-3">{{ session('message') }}</div>
            @endif

            <a href="{{ route('air.lounge') }}" class="lounge-btn mt-4">
                Book Another Lounge <x-ph-icon name="arrow-right" />
            </a>
        </div>
    </div>
</section>
@endcomponent
