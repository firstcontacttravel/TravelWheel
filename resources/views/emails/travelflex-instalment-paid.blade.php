@php
    $money = fn (float $value): string => 'NGN '.number_format($value, 2);
@endphp

<x-mail.layout
    :title="'Payment received — '.$bookingRef"
    :preheader="$money($amount).' received · booking '.$bookingRef"
    eyebrow="TravelFlex"
    heading="We've received your payment"
    :intro="'Hi '.$name.', thank you. We have recorded your payment for the instalment below.'"
    badge="Paid"
    tone="success"
>
    <x-mail.panel
        :label="$label"
        :value="$money($amount)"
        alt-label="Received on"
        :alt-value="$paidOn"
    />

    <x-mail.heading>Your plan</x-mail.heading>
    <x-mail.rows>
        <x-mail.row label="Booking reference">{{ $bookingRef }}</x-mail.row>
        <x-mail.row label="Payment reference">{{ $reference }}</x-mail.row>
        @if ($remainingCount > 0)
            <x-mail.row label="Instalments left">{{ $remainingCount }}</x-mail.row>
            <x-mail.row label="Left to pay">{{ $money($remainingAmount) }}</x-mail.row>
        @endif
    </x-mail.rows>

    @if ($remainingCount === 0)
        <x-mail.callout title="Your plan is fully repaid">
            This was your last instalment, so there is nothing more to pay on this booking. Thank you for travelling
            with TravelFlex.
        </x-mail.callout>
    @elseif ($nextLabel)
        <x-mail.callout title="Your next instalment">
            {{ $nextLabel }} is due on {{ $nextDue }}. We will remind you before it falls due.
        </x-mail.callout>
    @endif
</x-mail.layout>
