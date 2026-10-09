{{-- Line-by-line fees for one visa product, from VisaFeeEstimateService::estimate(). --}}
@php
    $lines = collect($estimate['lines'] ?? []);
    $online = $lines->where('pay_online', true);
    $direct = $lines->where('pay_online', false);
    $checkoutCurrency = $estimate['checkout']['currency'] ?? 'NGN';
    $processing = $estimate['processing_option'] ?? null;
    $travelerCount = $estimate['traveler_count'] ?? null;
    $money = fn (string $currency, float $amount) => $currency.' '.number_format($amount, 2);
@endphp
@if($lines->isNotEmpty())
<section class="vr-fees" aria-label="Fee breakdown">
    <header class="vr-fees__head">
        <strong>What you’ll pay</strong>
        <span>
            @if($travelerCount){{ $travelerCount }} traveler{{ $travelerCount === 1 ? '' : 's' }}@endif
            @if($processing) · {{ $processing['name'] }} processing ({{ $processing['minimum_business_days'] }}–{{ $processing['maximum_business_days'] }} business days)@endif
        </span>
    </header>

    @foreach(['online' => $online, 'direct' => $direct] as $group => $groupLines)
        @continue($groupLines->isEmpty())
        @if($group === 'direct')<p class="vr-fees__group">Paid separately at the embassy — not included in your online total</p>@endif
        <ul class="vr-fees__list {{ $group === 'direct' ? 'vr-fees__list--direct' : '' }}">
            @foreach($groupLines as $line)
                <li>
                    <div class="vr-fees__what">
                        <b>{{ $line['name'] }}</b>
                        <small>@if(isset($line['type_label']) && strcasecmp($line['type_label'], $line['name']) !== 0){{ $line['type_label'] }} · @endif{{ $line['basis_label'] ?? '' }}</small>
                        @if(!empty($line['explanation']))<small class="vr-fees__why">{{ $line['explanation'] }}</small>@endif
                    </div>
                    <div class="vr-fees__amount">
                        <strong>{{ $money($line['currency'], $line['amount']) }}</strong>
                        @if($line['pay_online'] && $line['currency'] !== $checkoutCurrency && ($line['checkout_amount'] ?? null) !== null)
                            <small>≈ {{ $money($checkoutCurrency, $line['checkout_amount']) }}</small>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endforeach

    @if(($estimate['processing_option_count'] ?? 0) > 1)
        <p class="vr-fees__foot">Faster processing options may cost more. You choose your option in the first step of the application.</p>
    @endif
</section>
@endif
