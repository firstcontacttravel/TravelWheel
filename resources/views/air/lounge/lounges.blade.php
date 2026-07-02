@component('layouts.app', ['title' => 'Available Lounges - TravelWheel'])

<section class="shadow-sm single-package">
    <div class="container py-4">
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if($lounges->isEmpty())
            <div class="row justify-content-center">
                <div class="col-sm-4 p-5">
                    <div class="card p-5 text-center">
                        <p>No Lounge found for the selected options.</p>
                        <a class="btn btn-primary" href="{{ route('air.lounge') }}">Back</a>
                    </div>
                </div>
            </div>
        @else
            <div class="row">
                @foreach($lounges as $lounge)
                <div class="col-sm-4 p-4">
                    <div class="card">
                        <img src="{{ asset('assets/lounge/' . $lounge->pics1) }}" class="img-fluid" alt="{{ $lounge->brand_name }}">
                        <div class="card-body">
                            <h5 class="card-title">{{ $lounge->brand_name }}</h5>
                            <ul class="list-unstyled">
                                <li><small><i class="fa fa-circle" style="font-size:6px"></i> {{ $lounge->facilities1 }}</small></li>
                                <li><small><i class="fa fa-circle" style="font-size:6px"></i> {{ $lounge->facilities2 }}</small></li>
                                <li><small><i class="fa fa-circle" style="font-size:6px"></i> {{ $lounge->facilities3 }}</small></li>
                                <li><small><i class="fa fa-circle" style="font-size:6px"></i> {{ $lounge->facilities4 }}</small></li>
                                <li><small><i class="fa fa-circle" style="font-size:6px"></i> {{ $lounge->facilities5 }}</small></li>
                            </ul>
                            <div class="col-12 text-center">
                                <p>₦{{ number_format($lounge->priceA) }}<span>/Passenger</span></p>
                                <a href="{{ route('air.loungeplans', ['id' => $lounge->id]) }}" class="btn btn-pry">Book Lounge</a>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

@endcomponent
