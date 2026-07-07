@include('air.insurance.partials.insurance-ui')

@php
    $formattedAmount = number_format((float) $quote->amount, 2);
    $isSchengen = (int) $quote->travelPlanId === 1;
    $planLabel = $isSchengen ? 'Schengen Plan' : 'Worldwide Plan';
    $planBenefits = $isSchengen
        ? ['Schengen visa compliant cover', 'Emergency medical & evacuation', 'Trip cancellation support', 'Baggage delay/loss cover']
        : ['Worldwide medical cover', 'Emergency medical & evacuation', 'Trip cancellation support', 'Baggage delay/loss cover'];
    $benefitsImage = $isSchengen ? 'Benefits2.jpg' : 'Benefits1.jpg';
@endphp

<section class="insurance-page">
    <div class="insurance-wrap">
        <div class="insurance-steps">
            <span class="insurance-step"><x-ph-icon name="clipboard-text" /> Get quote</span>
            <span class="insurance-step insurance-step-active"><x-ph-icon name="tag" /> Your quote</span>
            <span class="insurance-step"><x-ph-icon name="identification-card" /> Add details</span>
            <span class="insurance-step"><x-ph-icon name="credit-card" /> Pay</span>
        </div>

        <div class="insurance-hero">
            <div class="insurance-hero-main">
                <div class="insurance-kicker"><x-ph-icon name="shield-check" /> Travel Insurance Quote</div>
                <h5 class="insurance-title">Your {{ $planLabel }} quote is ready</h5>
                <p class="insurance-copy">
                    This quote covers {{ $quote->noOfPeople }} {{ (int) $quote->noOfPeople === 1 ? 'person' : 'people' }}
                    from {{ \Carbon\Carbon::parse($quote->coverBegins)->format('d M Y') }}
                    to {{ \Carbon\Carbon::parse($quote->coverEnds)->format('d M Y') }}.
                </p>
                <img src="{{ asset('assets/image/' . $benefitsImage) }}" class="insurance-benefits-img" alt="{{ $planLabel }} benefits">
                <ul class="insurance-list insurance-list-row">
                    @foreach($planBenefits as $benefit)
                        <li><x-ph-icon name="check-circle" /> {{ $benefit }}</li>
                    @endforeach
                </ul>
            </div>

            <div class="insurance-plan-simple">
                <img src="{{ asset('assets/img/allianz.png') }}" class="insurance-partner-logo" alt="Allianz">
                <span class="insurance-chip"><x-ph-icon name="shield-check" /> {{ $planLabel }}</span>
                <p class="insurance-price"><sup>₦</sup>{{ $formattedAmount }}</p>
                <a href="{{ route('air.insurance.request', ['qid' => $quote->id]) }}" class="insurance-btn w-100">
                    Proceed to Purchase <x-ph-icon name="arrow-right" />
                </a>
            </div>
        </div>
    </div>
</section>
