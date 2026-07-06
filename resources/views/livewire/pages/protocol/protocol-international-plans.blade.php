@include('air.protocol.partials.protocol-ui')

@php
    $formattedprice1 = number_format((float) $price1, 0, '', ','); // VIP
    $formattedprice2 = number_format((float) $price2, 0, '', ','); // Regular
    $showArrival = $service === 'Arrival';
    $showDeparture = $service === 'Departure';
    $benefits = [
        'Meet and greet',
        'Baggage handling',
        'Fast-track check-in',
        'Boarding/arrival escort',
        'Airport coordination',
    ];
@endphp

<section class="protocol-page">
    <div class="protocol-wrap">
        <div class="protocol-steps">
            <span class="protocol-step"><x-ph-icon name="map-pin" /> Select airports</span>
            <span class="protocol-step protocol-step-active"><x-ph-icon name="tag" /> Choose plan</span>
            <span class="protocol-step"><x-ph-icon name="identification-card" /> Add details</span>
            <span class="protocol-step"><x-ph-icon name="credit-card" /> Pay</span>
        </div>

        <div class="protocol-plans-row">
            <div class="protocol-hero-main">
                <div class="protocol-kicker"><x-ph-icon name="globe-hemisphere-east" /> International Airport Protocol</div>
                <h1 class="protocol-title">{{ $service }} assistance for international airports</h1>
                <p class="protocol-copy">
                    International protocol adds premium movement support around check-in, boarding, arrivals, and airport coordination.
                </p>
                <ul class="protocol-list protocol-list-row">
                    @foreach($benefits as $benefit)
                        <li><x-ph-icon name="check-circle" /> {{ $benefit }}</li>
                    @endforeach
                </ul>
            </div>

            @if($showDeparture)
                <div class="protocol-plan-simple protocol-plan-simple-plain">
                    <span class="protocol-chip"><x-ph-icon name="airplane-takeoff" /> Regular</span>
                    <p class="protocol-price"><sup>₦</sup>{{ $formattedprice2 }} <small>/ Passenger</small></p>
                    <a href="{{ route('air.protocolForm', ['plan' => 2]) }}" class="protocol-btn w-100">
                        Select Regular <x-ph-icon name="arrow-right" />
                    </a>
                </div>

                <div class="protocol-plan-simple">
                    <span class="protocol-chip"><x-ph-icon name="crown-simple" /> VIP</span>
                    <p class="protocol-price"><sup>₦</sup>{{ $formattedprice1 }} <small>/ Passenger</small></p>
                    <a href="{{ route('air.protocolForm', ['plan' => 1]) }}" class="protocol-btn w-100">
                        Select VIP <x-ph-icon name="arrow-right" />
                    </a>
                </div>
            @endif

            @if($showArrival)
                <div class="protocol-plan-simple">
                    <span class="protocol-chip"><x-ph-icon name="airplane-landing" /> Arrival VIP</span>
                    <p class="protocol-price"><sup>₦</sup>{{ $formattedprice1 }} <small>/ Passenger</small></p>
                    <a href="{{ route('air.protocolForm', ['plan' => 1]) }}" class="protocol-btn w-100">
                        Select VIP <x-ph-icon name="arrow-right" />
                    </a>
                </div>
            @endif
        </div>
    </div>
</section>
