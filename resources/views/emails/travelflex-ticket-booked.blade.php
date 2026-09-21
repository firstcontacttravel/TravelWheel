@php
    $flight = $booking->flight_snapshot ?? [];
    $segments = $flight['segments'] ?? [];
    $first = $segments[0] ?? [];
    $last = $segments ? $segments[array_key_last($segments)] : [];
    $route = trim(($first['from'] ?? '').' to '.($last['to'] ?? ''), ' to');
    $travelDate = ! empty($first['departDT'])
        ? \Carbon\Carbon::parse($first['departDT'])->format('D, d M Y')
        : null;
@endphp

<x-mail.layout
    :title="'Your flight is booked — '.$booking->booking_ref"
    :preheader="'Ticket issued · '.($route ?: 'your trip').' · '.$booking->booking_ref"
    eyebrow="TravelFlex"
    heading="Your flight is booked"
    intro="Your ticket has been issued and your TravelFlex repayment plan carries on exactly as scheduled."
    badge="Ticketed"
    tone="success"
>
    <x-mail.panel
        label="Booking reference"
        :value="$booking->booking_ref"
        mono
        alt-label="Travel date"
        :alt-value="$travelDate ?: '—'"
    />

    <x-mail.heading>Trip overview</x-mail.heading>
    <x-mail.rows>
        <x-mail.row label="Route">{{ $route ?: '—' }}</x-mail.row>
        <x-mail.row label="Airline">{{ $flight['airline'] ?? $booking->airline ?? '—' }}</x-mail.row>
    </x-mail.rows>

    <x-mail.callout title="Your repayments are unchanged">
        Booking your ticket does not alter your instalment dates or amounts. We will remind you before each one
        falls due.
    </x-mail.callout>
</x-mail.layout>
