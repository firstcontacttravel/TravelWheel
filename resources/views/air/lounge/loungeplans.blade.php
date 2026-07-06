@component('layouts.app', ['title' => 'Lounge Details - TravelWheel'])
@include('air.lounge.partials.lounge-ui')

<section class="lounge-page">
    <div class="lounge-wrap">
        @foreach($lounges as $lounge)
            @php $airport = $lounge->airport == 1 ? 'International' : 'Local'; @endphp
            <div class="lounge-hero">
                <div class="lounge-hero-main" style="padding:0; overflow:hidden;">
                    <div id="carouselLounge{{ $lounge->id }}" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <img src="{{ asset('assets/lounge/' . $lounge->pics1) }}" class="d-block w-100" style="aspect-ratio:4/3; object-fit:cover;" alt="">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('assets/lounge/' . $lounge->pics2) }}" class="d-block w-100" style="aspect-ratio:4/3; object-fit:cover;" alt="">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('assets/lounge/' . $lounge->pics3) }}" class="d-block w-100" style="aspect-ratio:4/3; object-fit:cover;" alt="">
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

                <div class="lounge-panel">
                    <h1 class="lounge-title">{{ $lounge->brand_name }}</h1>
                    <div class="lounge-kicker"><x-ph-icon name="map-pin" /> {{ $airport }} Airport</div>
                    <p class="lounge-copy">{{ $lounge->description }}</p>

                    <div class="lounge-panel-title mt-4"><x-ph-icon name="lifebuoy" /> Facilities</div>
                    <ul class="lounge-list">
                        <li><x-ph-icon name="wifi-high" /> {{ $lounge->facilities1 }}</li>
                        <li><x-ph-icon name="hamburger" /> {{ $lounge->facilities2 }}</li>
                        <li><x-ph-icon name="couch" /> {{ $lounge->facilities3 }}</li>
                        <li><x-ph-icon name="newspaper" /> {{ $lounge->facilities4 }}</li>
                        <li><x-ph-icon name="bell" /> {{ $lounge->facilities5 }}</li>
                    </ul>

                    <div class="lounge-total-row mt-4" style="align-items:center;">
                        <p class="lounge-price" style="margin:0;"><sup>₦</sup>{{ number_format($lounge->priceA) }} <small>/ Passenger</small></p>
                        <a href="{{ route('air.loungebooking', ['id' => $lounge->id]) }}" class="lounge-btn">
                            Proceed <x-ph-icon name="arrow-right" />
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>

@endcomponent
