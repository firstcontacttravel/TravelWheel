@component('layouts.app', ['title' => 'Insurance Booking Successful - TravelWheel'])
@include('air.insurance.partials.insurance-ui')

<section class="insurance-page">
    <div class="insurance-wrap">
        <div class="insurance-success">
            @if(session('insurance_pending_review'))
                <div class="insurance-success-icon"><x-ph-icon name="clock" /></div><br>
                <div class="insurance-kicker justify-content-center"><x-ph-icon name="shield-check" /> Payment Received</div>
                <h1 class="insurance-title">Your policy is being finalized</h1>
                <p class="insurance-copy">
                    We've received your payment, but couldn't confirm your policy automatically. Our team has been notified and will email your policy documents shortly.
                </p>
            @else
                <div class="insurance-success-icon"><x-ph-icon name="check" /></div><br>
                <div class="insurance-kicker justify-content-center"><x-ph-icon name="shield-check" /> Booking Successful</div>
                <h1 class="insurance-title">Travel insurance booked</h1>
                <p class="insurance-copy">
                    Your travel insurance has been purchased successfully. A confirmation will be sent to your email address.
                </p>
            @endif

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
