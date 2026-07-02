@component('layouts.app', ['title' => 'Protocol Booking Successful - TravelWheel'])

<section class="shadow-sm py-5">
    <div class="container">
        <div class="row">
            <div class="col-sm-3"></div>
            <div class="col-sm-6 pt-3 pb-5">
                <div class="p-3 text-center">
                    <img src="{{ asset('assets/img/77suc.gif') }}" class="w-auto mb-3" alt="Success" style="max-height:120px;">
                    <h4>Purchase Successful!</h4>
                    <p>You have successfully booked a Protocol Service.
                    Please check your inbox (or spam/junk) for the email provided — your Protocol Service Pass will be sent there.</p>

                    @if(session('message'))
                        <div class="alert alert-info">{{ session('message') }}</div>
                    @endif

                    <div class="row justify-content-center mt-3">
                        <div class="col-auto">
                            <a href="{{ route('air.protocol') }}" class="btn btn-primary">Back to Protocol</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-3"></div>
        </div>
    </div>
</section>
@endcomponent
