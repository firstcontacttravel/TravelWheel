@component('layouts.app', ['title' => 'Insurance Booking Successful - TravelWheel'])
@include('air.insurance.partials.insurance-ui')

<section class="insurance-page">
    <div class="insurance-wrap">
        <div class="insurance-success">
            <div class="insurance-success-icon"><x-ph-icon name="check" /></div><br>
            <div class="insurance-kicker justify-content-center"><x-ph-icon name="shield-check" /> Booking Successful</div>
            <h1 class="insurance-title">Travel insurance booked</h1>
            <p class="insurance-copy">
                Your travel insurance has been purchased successfully. A confirmation will be sent to your email address.
            </p>

            @if(session('message'))
                <div class="alert alert-info mt-3">{{ session('message') }}</div>
            @endif

            <a href="{{ route('air.insurance') }}" class="insurance-btn mt-4">
                Book Another Policy <x-ph-icon name="arrow-right" />
            </a>
        </div>
    </div>
</section>
@endcomponent
