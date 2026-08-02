@include('air.lounge.partials.lounge-ui')

<section class="lounge-page">
    <div class="lounge-wrap">
        <div class="lounge-hero-main mb-4">
            <div class="lounge-kicker"><x-ph-icon name="couch" /> Available Lounges</div>
            <h1 class="lounge-title">Choose a lounge</h1>
            <p class="lounge-copy">Pick the lounge that matches your terminal and budget.</p>
        </div>

        @if(session('error'))
            <div class="lounge-note mb-4">{{ session('error') }}</div>
        @endif

        @if($lounges->isEmpty())
            <div class="lounge-panel text-center">
                <p class="lounge-copy">No lounge found for the selected options.</p>
                <a class="lounge-btn mt-3" href="{{ route('air.lounge') }}">
                    <x-ph-icon name="arrow-left" /> Back
                </a>
            </div>
        @else
            <div class="lounge-plan-grid">
                @foreach($lounges as $lounge)
                    <div class="lounge-plan">
                        <img src="{{ asset('assets/lounge/' . $lounge->pics1) }}" alt="{{ $lounge->brand_name }}" style="width:100%; aspect-ratio:16/9; object-fit:cover; border-radius:8px;">
                        <div class="lounge-plan-head">
                            <h4>{{ $lounge->brand_name }}</h4>
                        </div>
                        <ul class="lounge-list-row">
                            <li><x-ph-icon name="check-circle" /><span class="lounge-list-row-text">{{ $lounge->facilities1 }}</span></li>
                            <li><x-ph-icon name="check-circle" /><span class="lounge-list-row-text">{{ $lounge->facilities2 }}</span></li>
                            <li><x-ph-icon name="check-circle" /><span class="lounge-list-row-text">{{ $lounge->facilities3 }}</span></li>
                            <li><x-ph-icon name="check-circle" /><span class="lounge-list-row-text">{{ $lounge->facilities4 }}</span></li>
                            <li><x-ph-icon name="check-circle" /><span class="lounge-list-row-text">{{ $lounge->facilities5 }}</span></li>
                        </ul>
                        <div>
                            <p class="lounge-price">
                                <span class="lounge-price-amount"><sup>₦</sup>{{ number_format($lounge->priceA) }}</span>
                                <small>/ Passenger</small>
                            </p>
                            <a href="{{ route('air.loungeplans', ['id' => $lounge->id]) }}" class="lounge-btn w-100 mt-3">
                                Book Lounge <x-ph-icon name="arrow-right" />
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
