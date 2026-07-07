@include('air.protocol.partials.protocol-ui')

@php
    $formattedprice1 = number_format((float) $price1, 0, '', ','); // VIP
    $formattedprice2 = number_format((float) $price2, 0, '', ','); // Regular
    $showArrival = $service === 'Arrival';
    $showDeparture = $service === 'Departure';

    $regularBenefits = ['Meet and greet', 'Exclusive baggage handling', 'No queuing', 'Stress-free check-in process', 'Other relevant airport protocol service'];
    $vipDepartureBenefits = ['Meet and greet', 'Exclusive baggage handling', 'Fast-tracking check-in process', 'No queuing', 'Stress-free check-in process', 'Escort through boarding gate', 'Other relevant airport protocol service'];
    $vipArrivalBenefits = ['Meet and greet', 'Exclusive baggage handling', 'Escort to the arrival lobby', 'Coordinate passenger to pre-arranged transportation', 'Other relevant airport protocol service'];

    $allBenefits = [
        'Meet and greet',
        'Exclusive baggage handling',
        'No queue',
        'Fast-tracking check-in process',
        'Stress-free check-in process',
        'Escort through boarding gate',
        'Escort to the arrival lobby',
        'Coordinate passenger to pre-arranged transportation',
        'Other relevant airport protocol service as case may be',
    ];
    $optionalBenefits = ['Pick-up request', 'Drop-off request', 'Police escort request'];
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

            <div class="protocol-plans-group">
                @if($showDeparture)
                    <div class="protocol-plan-simple protocol-plan-simple-plain">
                        <span class="protocol-chip"><x-ph-icon name="airplane-takeoff" /> Regular</span>
                        <p class="protocol-price"><sup>₦</sup>{{ $formattedprice2 }} <small>/ Passenger</small></p>
                        <ul class="protocol-list">
                            @foreach($regularBenefits as $benefit)
                                <li><x-ph-icon name="check-circle" /> {{ $benefit }}</li>
                            @endforeach
                        </ul>
                        <a href="{{ route('air.protocolForm', ['plan' => 2]) }}" class="protocol-btn w-100">
                            Select Regular <x-ph-icon name="arrow-right" />
                        </a>
                    </div>

                    <div class="protocol-plan-simple">
                        <span class="protocol-chip"><x-ph-icon name="crown-simple" /> VIP</span>
                        <p class="protocol-price"><sup>₦</sup>{{ $formattedprice1 }} <small>/ Passenger</small></p>
                        <ul class="protocol-list">
                            @foreach($vipDepartureBenefits as $benefit)
                                <li><x-ph-icon name="check-circle" /> {{ $benefit }}</li>
                            @endforeach
                        </ul>
                        <a href="{{ route('air.protocolForm', ['plan' => 1]) }}" class="protocol-btn w-100">
                            Select VIP <x-ph-icon name="arrow-right" />
                        </a>
                    </div>
                @endif

                @if($showArrival)
                    <div class="protocol-plan-simple">
                        <span class="protocol-chip"><x-ph-icon name="airplane-landing" /> Arrival VIP</span>
                        <p class="protocol-price"><sup>₦</sup>{{ $formattedprice1 }} <small>/ Passenger</small></p>
                        <ul class="protocol-list">
                            @foreach($vipArrivalBenefits as $benefit)
                                <li><x-ph-icon name="check-circle" /> {{ $benefit }}</li>
                            @endforeach
                        </ul>
                        <a href="{{ route('air.protocolForm', ['plan' => 1]) }}" class="protocol-btn w-100">
                            Select VIP <x-ph-icon name="arrow-right" />
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
