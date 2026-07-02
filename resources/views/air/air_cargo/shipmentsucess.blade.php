@component('layouts.app', ['title' => 'Shipment Successful - TravelWheel'])

<section class="shadow-sm">
    <div class="container">
        <div class="row justify-content-center py-5">
            <div class="col-sm-7 text-center">
                <div class="card shadow p-4">
                    <div class="card-body">
                        <div style="font-size:80px; color:rgba(13,156,83,1);">
                            <i class="fa fa-check-circle"></i>
                        </div>
                        <h3 class="mt-3">Shipment Booking Successful!</h3>
                        @if(session('data'))
                            @php $shipData = session('data'); @endphp
                            <p class="text-muted">
                                Your shipment <strong>{{ $shipData['shipping_id'] }}</strong> has been booked.
                                A confirmation document has been sent to <strong>{{ $shipData['sender_email'] }}</strong>.
                            </p>
                        @else
                            <p class="text-muted">Your shipment has been booked. A confirmation document has been sent to your email.</p>
                        @endif
                        <a href="{{ route('air.cargo') }}" class="btn btn-pry mt-3">Book Another Shipment</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endcomponent
