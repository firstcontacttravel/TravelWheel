{{-- resources/views/emails/booking-pending.blade.php --}}
@php
    $passengers = \App\Support\FlightDisplay::passengers($booking->passengers_snapshot ?? []);
    $cabinLabel = \App\Support\FlightDisplay::cabin($booking->flight_snapshot ?? [], $booking);
    $firstPax = collect($passengers)->first();
    $firstName = $firstPax['first_name'] ?? 'Traveller';
    $currency = $booking->currency ?? 'NGN';
    $sym = match ($currency) { 'NGN' => 'NGN ', 'USD' => '$', 'GBP' => 'GBP ', 'EUR' => 'EUR ', default => $currency.' ' };
    $price = $sym.number_format((float) ($booking->total_price ?? 0), 2);

    $resumePaymentUrl = $resumePaymentUrl ?? null;
    $paymentMethod = $paymentMethod ?? ($method ?? 'bank_transfer');
    $isHoldNotice = $isHoldNotice ?? $paymentMethod === 'hold';
    $isBankTransferNotice = $isBankTransferNotice ?? $paymentMethod === 'bank_transfer';

    $tktFmt = null;
    $tktHours = null;
    if ($booking->tkt_time_limit) {
        try {
            $deadline = \Carbon\Carbon::parse($booking->tkt_time_limit);
            $tktFmt = $deadline->timezone('Africa/Lagos')->format('D, d M Y \a\t H:i');
            $tktHours = max(0, (int) now()->diffInHours($deadline, false));
        } catch (\Throwable) {
            $tktFmt = null;
        }
    }

    $heading = $isHoldNotice ? 'Your booking is on hold' : 'We are verifying your payment';
    $intro = $isHoldNotice
        ? 'your seat is reserved with the airline while payment is pending. Complete payment before the deadline to keep this fare.'
        : 'we have your payment notification. Your booking stays reserved while our team confirms the transfer.';
@endphp

<x-mail.layout
    :title="'Booking pending — '.$booking->booking_ref"
    :preheader="($isHoldNotice ? 'Action needed: complete payment' : 'Payment under review').' · '.($booking->route ?: 'Your trip').' · '.$booking->booking_ref"
    eyebrow="Flight booking"
    :heading="$heading"
    :intro="'Hi '.$firstName.', '.$intro"
    :badge="$isHoldNotice ? 'Payment due' : 'Under review'"
    tone="warning"
>

    <x-mail.panel
        label="Booking reference"
        :value="$booking->booking_ref"
        mono
        alt-label="Amount due"
        :alt-value="$price"
        :alt-sublabel="$cabinLabel"
    />

    @if ($tktFmt)
        <x-mail.callout tone="warning" title="Your hold expires {{ $tktFmt }}">
            @if ($tktHours !== null)
                That is about {{ $tktHours }} hour{{ $tktHours === 1 ? '' : 's' }} from now.
            @endif
            After that the airline releases the seat and this fare may no longer be available.
        </x-mail.callout>
    @endif

    @if ($resumePaymentUrl)
        <x-mail.button :url="$resumePaymentUrl">Complete payment</x-mail.button>
        <x-mail.link-fallback :url="$resumePaymentUrl" />
    @endif

    <x-mail.heading>Trip overview</x-mail.heading>
    <x-mail.rows>
        <x-mail.row label="Route">{{ $booking->route ?: '—' }}</x-mail.row>
        <x-mail.row label="Airline">{{ $booking->airline ?: '—' }}</x-mail.row>
        <x-mail.row label="Cabin">{{ $cabinLabel }}</x-mail.row>
        <x-mail.row label="Travellers">{{ count($passengers) ?: 1 }}</x-mail.row>
        <x-mail.row label="Payment option">{{ $isBankTransferNotice ? 'Bank transfer' : 'Online or bank transfer' }}</x-mail.row>
    </x-mail.rows>

    @unless ($isHoldNotice)
        <x-mail.callout title="What happens next">
            Bank transfers are usually confirmed within a few working hours. As soon as yours clears we will issue
            your ticket and email it to this address — there is nothing else for you to do.
        </x-mail.callout>
    @endunless

</x-mail.layout>
