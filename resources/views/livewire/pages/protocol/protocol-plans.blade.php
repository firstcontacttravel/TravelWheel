@include('air.protocol.partials.protocol-ui')

@php
    $formattedprice1 = number_format((float) $price1, 0, '', ',');
    $showArrival = $service === 'Arrival';
    $showDeparture = $service === 'Departure';
    $planIcon = $showDeparture ? 'airplane-takeoff' : 'airplane-landing';
    $planBenefits = $showDeparture
        ? ['Meet and greet', 'Exclusive baggage handling', 'Fast-tracking check-in process', 'No queuing', 'Pre check-in process', 'Other relevant airport protocol service']
        : ['Meet and greet', 'Exclusive baggage handling', 'Escort to the arrival lobby', 'Coordinate passenger to pre-arranged transportation', 'Other relevant airport protocol service'];

    $allBenefits = [
        'Meet and greet',
        'Exclusive baggage handling',
        'No queue',
        'Fast-tracking check-in process',
        'Stress-free check-in process',
        'Escort to the arrival lobby',
        'Coordinate passenger to pre-arranged transportation',
        'Other relevant airport protocol service as case may be',
    ];
    $optionalBenefits = ['Pick-up request', 'Drop-off request', 'Police escort request'];
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
                <ul class="protocol-list protocol-list-sm" style="margin-top:16px;">
                    @foreach($allBenefits as $benefit)
                        <li><x-ph-icon name="check-circle" /> {{ $benefit }}</li>
                    @endforeach
                    @foreach($optionalBenefits as $benefit)
                        <li><x-ph-icon name="plus-circle" /> {{ $benefit }} <small>(Optional)</small></li>
                    @endforeach
                </ul>
                <div class="protocol-note">
                    <x-ph-icon name="info" /> NB: A Protocol Boarding Pass will be generated after a Successful transaction. It expires after Departure / Arrival date.
                </div>
            </div>

            <div class="protocol-plan-simple">
                <span class="protocol-chip"><x-ph-icon :name="$planIcon" /> {{ $service }}</span>
                <p class="protocol-price"><sup>₦</sup>{{ $formattedprice1 }} <small>/ Passenger</small></p>
                <ul class="protocol-list">
                    @foreach($planBenefits as $benefit)
                        <li><x-ph-icon name="check-circle" /> {{ $benefit }}</li>
                    @endforeach
                </ul>
                <a href="{{ route('air.protocolForm', ['plan' => 1]) }}" class="protocol-btn w-100">
                    Select Plan <x-ph-icon name="arrow-right" />
                </a>
            </div>
        </div>
    </div>
</section>
