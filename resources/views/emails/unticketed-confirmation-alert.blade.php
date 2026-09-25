{{-- Internal — flight ops alert, not a customer email. --}}
@php
    $passengers = \App\Support\FlightDisplay::passengers($data['passengers'] ?? []);
    $c = config('brand.colors');
    $font = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,Helvetica,sans-serif";
@endphp

<x-mail.layout
    title="Unticketed confirmed booking"
    :preheader="'Confirmed but not ticketed · '.$data['uniqueId']"
    internal
    eyebrow="Internal · flight ops"
    heading="Confirmed booking has not been ticketed"
    intro="The airline confirmed this booking but no ticket has been issued. It needs a look now."
    badge="Investigate"
    tone="danger"
>

    <x-mail.panel
        label="Booking reference"
        :value="$data['uniqueId']"
        mono
        alt-label="Status"
        :alt-value="$data['bookingStatus']"
        :alt-sublabel="'Ticketing: '.$data['ticketStatus']"
    />

    <x-mail.heading>Flight</x-mail.heading>
    <x-mail.rows>
        <x-mail.row label="Route">{{ $data['origin'] }} &rarr; {{ $data['destination'] }}</x-mail.row>
        <x-mail.row label="Fare type">{{ $data['fareType'] }}</x-mail.row>
        <x-mail.row label="Detected at">{{ $data['timestamp']->timezone('Africa/Lagos')->format('D, d M Y \a\t H:i') }}</x-mail.row>
    </x-mail.rows>

    @if (! empty($passengers))
        <x-mail.heading>Passengers</x-mail.heading>
        <x-mail.rows>
            @foreach ($passengers as $pax)
                <x-mail.row :label="trim(($pax['title'] ?? '').' '.($pax['first_name'] ?? '').' '.($pax['last_name'] ?? ''))">
                    {{ $pax['type'] ?? 'ADT' }} &nbsp;·&nbsp; {{ $pax['email'] ?? '—' }}
                </x-mail.row>
            @endforeach
        </x-mail.rows>
    @endif

    <x-mail.callout tone="danger" title="Why this fires">
        The airline returned CONFIRMED without a ticket number. Usually that is payment still settling, a
        ticketing-system delay, or a fare that needs issuing by hand. Check which before the fare expires.
    </x-mail.callout>

</x-mail.layout>
