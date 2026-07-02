@component('layouts.app', ['title' => 'Lounge Details - TravelWheel'])

<section class="shadow-sm">
    <div class="container-fluid p-5">
        @foreach($lounges as $lounge)
        <div class="row single-package mb-5">
            <div class="col-sm-6 p-4">
                <div id="carouselLounge{{ $lounge->id }}" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <div class="carousel-item active">
                            <img src="{{ asset('assets/lounge/' . $lounge->pics1) }}" class="d-block w-100" alt="">
                        </div>
                        <div class="carousel-item">
                            <img src="{{ asset('assets/lounge/' . $lounge->pics2) }}" class="d-block w-100" alt="">
                        </div>
                        <div class="carousel-item">
                            <img src="{{ asset('assets/lounge/' . $lounge->pics3) }}" class="d-block w-100" alt="">
                        </div>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#carouselLounge{{ $lounge->id }}" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#carouselLounge{{ $lounge->id }}" data-bs-slide="next">
                        <span class="carousel-control-next-icon"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            </div>
            <div class="col-sm-6 p-4">
                <div class="col-12 p-3">
                    <h3>{{ $lounge->brand_name }}</h3>
                    <div class="protocol">
                        <h6><i class="fa-solid fa-location-dot" style="color:green"></i> Location</h6>
                        <span class="text-muted" style="font-size:14px;">{{ $lounge->description }}</span>
                    </div>
                    <div class="protocol pt-3">
                        <h6><i class="fas fa-life-ring" style="color:green"></i> Facilities</h6>
                        <ul class="list-unstyled">
                            <li class="pb-1"><i class="fas fa-wifi" style="color:green"></i> {{ $lounge->facilities1 }}</li>
                            <li class="pb-1"><i class="fas fa-hamburger" style="color:green"></i> {{ $lounge->facilities2 }}</li>
                            <li class="pb-1"><i class="fas fa-couch" style="color:green"></i> {{ $lounge->facilities3 }}</li>
                            <li class="pb-1"><i class="fas fa-newspaper" style="color:green"></i> {{ $lounge->facilities4 }}</li>
                            <li class="pb-1"><i class="fas fa-concierge-bell" style="color:green"></i> {{ $lounge->facilities5 }}</li>
                        </ul>
                        <div class="row">
                            <div class="col-6 text-end">
                                <p><sup>₦</sup>{{ number_format($lounge->priceA) }}<span>/Passenger</span></p>
                            </div>
                            <div class="col-6 text-start">
                                <a href="{{ route('air.loungebooking', ['id' => $lounge->id]) }}" class="btn btn-pry">Proceed</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</section>

@endcomponent
