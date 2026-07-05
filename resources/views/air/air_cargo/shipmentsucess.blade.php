@component('layouts.app', ['title' => 'Shipment Successful - TravelWheel'])
@include('air.air_cargo.partials.cargo-ui')

<section class="cargo-page">
    <div class="cargo-wrap">
        <div class="cargo-success">
            <div class="cargo-success-icon"><x-ph-icon name="check" /></div> <br>
            <div class="cargo-kicker justify-content-center"><x-ph-icon name="shield-check" /> Booking Successful</div>
            <h1 class="cargo-title">Shipment booked</h1>

            @if(session('data'))
                @php $shipData = session('data'); @endphp
                <p class="cargo-copy">
                    Your shipment has been booked and a confirmation document has been sent to the email address provided.
                </p>

                <div class="cargo-success-detail">
                    <div>
                        <span>Shipping ID</span>
                        <strong>{{ $shipData['shipping_id'] ?? '—' }}</strong>
                    </div>
                    <div>
                        <span>Confirmation Sent To</span>
                        <strong>{{ $shipData['sender_email'] ?? '—' }}</strong>
                    </div>
                </div>
            @else
                <p class="cargo-copy">Your shipment has been booked. A confirmation document has been sent to your email.</p>
            @endif

            <a href="{{ route('air.cargo') }}" class="cargo-btn mt-2">
                Book Another Shipment <x-ph-icon name="arrow-right" />
            </a>
        </div>
    </div>
</section>
@endcomponent
