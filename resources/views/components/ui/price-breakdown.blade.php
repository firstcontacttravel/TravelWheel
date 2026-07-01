@props([
    'items' => [],
    'directItems' => [],
    'total',
    'currency' => 'NGN',
    'totalLabel' => 'Pay now',
])

@php
    $money = static fn ($amount) => $currency.' '.number_format((float) $amount, 2);
@endphp

<div {{ $attributes->class('tw-ui-price') }}>
    @foreach($items as $item)
        <div class="tw-ui-price__row">
            <span class="tw-ui-price__label">{{ $item['label'] }}@if(!empty($item['meta']))<span class="tw-ui-price__meta">{{ $item['meta'] }}</span>@endif</span>
            <span class="tw-ui-price__amount">{{ $money($item['amount']) }}</span>
        </div>
    @endforeach

    @if(count($directItems))
        <div class="tw-ui-price__direct">
            <p class="tw-ui-price__group-title">Pay separately to the authority</p>
            @foreach($directItems as $item)
                <div class="tw-ui-price__row">
                    <span class="tw-ui-price__label">{{ $item['label'] }}</span>
                    <span class="tw-ui-price__amount">{{ $money($item['amount']) }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <div class="tw-ui-price__total">
        <span>{{ $totalLabel }}</span>
        <span class="tw-ui-price__total-amount">{{ $money($total) }}</span>
    </div>
</div>
