@include('air.protocol.partials.protocol-ui')

@php
    $formattedprice1 = number_format((float) $price1, 0, '', ',');
    $showArrival = $service === 'Arrival';
    $showDeparture = $service === 'Departure';
    $planIcon = $showDeparture ? 'airplane-takeoff' : 'airplane-landing';
    $planBenefits = $showDeparture
        ? ['Meet and greet', 'Baggage handling', 'Fast-track check-in', 'No queueing support', 'Pre check-in process']
        : ['Meet and greet', 'Baggage handling', 'Escort to arrival lobby', 'Transport coordination', 'Other protocol service'];
@endphp

<section class="protocol-page ">
    <div class="protocol-wrap">
        <div class="protocol-steps">
            <span class="protocol-step"><x-ph-icon name="map-pin" /> Select airports</span>
            <span class="protocol-step protocol-step-active"><x-ph-icon name="tag" /> Choose plan</span>
            <span class="protocol-step"><x-ph-icon name="identification-card" /> Add details</span>
            <span class="protocol-step"><x-ph-icon name="credit-card" /> Pay</span>
        </div>

        <div class="protocol-hero">
            <div class="protocol-hero-main">
                <div class="protocol-kicker"><x-ph-icon name="airplane" /> Local Airport Protocol</div>
                <h5 class="protocol-title">{{ $service }} assistance for local airports</h5>
                <p class="protocol-copy">
                    Choose the service plan that matches your local airport movement. Covers your selected {{ strtolower($service) }} segment, priced per passenger.
                </p>
                <ul class="protocol-list protocol-list-row">
                    @foreach($planBenefits as $benefit)
                        <li><x-ph-icon name="check-circle" /> {{ $benefit }}</li>
                    @endforeach
                </ul>
            </div>

            <div class="protocol-plan-simple">
                <span class="protocol-chip"><x-ph-icon :name="$planIcon" /> {{ $service }}</span>
                <p class="protocol-price"><sup>₦</sup>{{ $formattedprice1 }} <small>/ Passenger</small></p>
                <a href="{{ route('air.protocolForm', ['plan' => 1]) }}" class="protocol-btn w-100">
                    Select Plan <x-ph-icon name="arrow-right" />
                </a>
            </div>
        </div>
    </div>
</section>
