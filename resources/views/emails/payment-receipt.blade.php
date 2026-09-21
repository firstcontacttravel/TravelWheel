{{-- resources/views/emails/payment-receipt.blade.php --}}
@php
    $passengers = \App\Support\FlightDisplay::passengers($booking->passengers_snapshot ?? []);
    $firstPax = collect($passengers)->first();
    $firstName = $firstPax['first_name'] ?? 'Traveller';
    $cabinLabel = \App\Support\FlightDisplay::cabin($booking->flight_snapshot ?? [], $booking);
    $currency = $booking->payment_currency ?: ($booking->currency ?? 'NGN');
    $sym = match ($currency) { 'NGN' => 'NGN ', 'USD' => '$', 'GBP' => 'GBP ', 'EUR' => 'EUR ', default => $currency.' ' };
    $expectedAmount = (float) ($booking->payment_amount ?? $booking->total_price ?? 0);
    $chargedAmount = (float) ($booking->payment_charged_amount ?? $expectedAmount);
    $charges = max(0, $chargedAmount - $expectedAmount);
    $extraServices = $booking->extra_services_snapshot ?? [];
    $baggageItems = $extraServices['baggage'] ?? [];
    $mealItems = $extraServices['meal'] ?? [];
    $paidOn = $booking->payment_verified_at
        ? \Carbon\Carbon::parse($booking->payment_verified_at)->timezone('Africa/Lagos')->format('D, d M Y \a\t H:i')
        : null;
@endphp

<x-mail.layout
    :title="'Payment receipt — '.$booking->booking_ref"
    :preheader="'Receipt for '.$sym.number_format($chargedAmount, 2).' · '.$booking->booking_ref"
    eyebrow="Payment receipt"
    heading="Thanks — your payment went through"
    :intro="'Hi '.$firstName.', this is your receipt. Keep it for your records; you do not need to do anything with it.'"
    badge="Paid"
    tone="success"
>

    <x-mail.panel
        label="Booking reference"
        :value="$booking->booking_ref"
        mono
        alt-label="Total charged"
        :alt-value="$sym.number_format($chargedAmount, 2)"
        :alt-sublabel="$paidOn"
    />

    <x-mail.heading>Payment</x-mail.heading>
    <x-mail.rows>
        <x-mail.row label="Payment reference">{{ $booking->payment_reference ?: '—' }}</x-mail.row>
        {{-- ucfirst() turned the provider's own name into "Seerbit". --}}
        <x-mail.row label="Gateway">{{ ['seerbit' => 'SeerBit'][strtolower((string) $booking->payment_gateway)] ?? (filled($booking->payment_gateway) ? ucfirst($booking->payment_gateway) : 'SeerBit') }}</x-mail.row>
        <x-mail.row label="Fare and taxes">{{ $sym }}{{ number_format($expectedAmount, 2) }}</x-mail.row>
        @if ($charges > 0)
            <x-mail.row label="Gateway charges">{{ $sym }}{{ number_format($charges, 2) }}</x-mail.row>
        @endif
        <x-mail.row label="Total charged" strong>{{ $sym }}{{ number_format($chargedAmount, 2) }}</x-mail.row>
    </x-mail.rows>

    @if (! empty($baggageItems) || ! empty($mealItems))
        <x-mail.heading>Extras included</x-mail.heading>
        <x-mail.rows>
            @foreach ($baggageItems as $bag)
                <x-mail.row :label="($bag['description'] ?? 'Baggage').' × '.($bag['quantity'] ?? 1)">
                    {{ $sym }}{{ number_format((float) ($bag['line_total'] ?? 0), 2) }}
                </x-mail.row>
            @endforeach
            @foreach ($mealItems as $meal)
                <x-mail.row :label="$meal['description'] ?? 'Meal'">
                    {{ $sym }}{{ number_format((float) ($meal['unit_price'] ?? 0), 2) }}
                </x-mail.row>
            @endforeach
        </x-mail.rows>
    @endif

    <x-mail.heading>Trip</x-mail.heading>
    <x-mail.rows>
        <x-mail.row label="Route">{{ $booking->route ?: '—' }}</x-mail.row>
        <x-mail.row label="Cabin">{{ $cabinLabel }}</x-mail.row>
        <x-mail.row label="Travellers">{{ count($passengers) ?: 1 }}</x-mail.row>
    </x-mail.rows>

</x-mail.layout>
