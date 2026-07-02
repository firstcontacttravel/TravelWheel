@component('layouts.app', ['title' => 'Insurance Booking Successful - TravelWheel'])

<section class="shadow-sm">
    <div class="container">
        <div class="row justify-content-center py-5">
            <div class="col-sm-7 text-center">
                <div class="card shadow p-4">
                    <div class="card-body">
                        <div style="font-size:80px; color:rgba(13,156,83,1);">
                            <i class="fa fa-check-circle"></i>
                        </div>
                        <h3 class="mt-3">Booking Successful!</h3>
                        <p class="text-muted">
                            Your travel insurance has been purchased successfully.
                            A confirmation will be sent to your email address.
                        </p>
                        <a href="{{ route('air.insurance') }}" class="btn btn-pry mt-3">
                            Book Another Policy
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endcomponent
