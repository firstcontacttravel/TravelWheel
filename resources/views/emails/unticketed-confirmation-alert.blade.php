{{-- Internal — flight ops alert, not a customer email. --}}
@php
    $passengers = \App\Support\FlightDisplay::passengers($data['passengers'] ?? []);
    $c = config('brand.colors');
    $font = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,Helvetica,sans-serif";

    // SkyLink reserves only after payment and issues tickets outside its API,
    // so its alerts are a different problem from TravelNext's.
    $case = match (true) {
        ($data['supplier'] ?? '') === 'skylink' && ! ($data['reserved'] ?? true) => 'skylink-not-reserved',
        ($data['supplier'] ?? '') === 'skylink' => 'skylink-awaiting-ticket',
        default => 'travelnext',
    };
    $copy = [
        'skylink-not-reserved' => [
            'title' => 'Paid booking not reserved',
            'preheader' => 'Paid but no seat reserved',
            'heading' => 'Paid booking has no seat reserved',
            'intro' => 'The customer has paid, but SkyLink did not reserve the seat. Nothing is booked yet. Rebook or refund.',
        ],
        'skylink-awaiting-ticket' => [
            'title' => 'SkyLink booking needs ticketing',
            'preheader' => 'Reserved, ticket not issued',
            'heading' => 'Paid SkyLink booking still needs its ticket',
            'intro' => 'SkyLink has reserved the seat, but the ticket has not been issued. It needs a look before the seat deadline.',
        ],
        'travelnext' => [
            'title' => 'Unticketed confirmed booking',
            'preheader' => 'Confirmed but not ticketed',
            'heading' => 'Confirmed booking has not been ticketed',
            'intro' => 'The airline confirmed this booking but no ticket has been issued. It needs a look now.',
        ],
    ][$case];
@endphp

<x-mail.layout
    :title="$copy['title']"
    :preheader="$copy['preheader'].' · '.$data['uniqueId']"
    internal
    eyebrow="Internal · flight ops"
    :heading="$copy['heading']"
    :intro="$copy['intro']"
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

    @if (filled($data['reason'] ?? null))
        <x-mail.heading>Reason</x-mail.heading>
        <x-mail.rows>
            <x-mail.row label="Reported">{{ $data['reason'] }}</x-mail.row>
            @if (filled($data['seatDeadline'] ?? null))
                <x-mail.row label="Seat held until">{{ $data['seatDeadline'] }} (Lagos)</x-mail.row>
            @endif
        </x-mail.rows>
    @endif

    <x-mail.heading>Flight</x-mail.heading>
    <x-mail.rows>
        @if (($data['tripType'] ?? '') === 'Multi-city')
            <x-mail.row label="Route">{{ collect($data['legs'])->pluck('from')->push(collect($data['legs'])->last()['to'] ?? '')->filter()->implode(' → ') }}</x-mail.row>
        @else
            <x-mail.row label="Route">{{ $data['origin'] }} &rarr; {{ $data['destination'] }}</x-mail.row>
        @endif
        @if (! empty($data['tripType']))
            <x-mail.row label="Trip">{{ $data['tripType'] }}</x-mail.row>
        @endif
        @foreach ($data['legs'] ?? [] as $leg)
            <x-mail.row :label="$leg['label']">
                {{ $leg['from'] }} &rarr; {{ $leg['to'] }}@if ($leg['date']) &nbsp;·&nbsp; {{ $leg['date'] }}@endif @if ($leg['flights']) &nbsp;·&nbsp; {{ $leg['flights'] }}@endif
            </x-mail.row>
        @endforeach
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

    @if ($case === 'skylink-not-reserved')
        <x-mail.callout tone="danger" title="Why this fires">
            SkyLink reserves a seat only after the customer has paid, so a reservation that fails leaves a paid
            booking with nothing held. The SkyLink error is in the reason above (every call is also logged in
            flight_supplier_calls). Rebook the same or another fare, or refund the customer.
        </x-mail.callout>
    @elseif ($case === 'skylink-awaiting-ticket')
        <x-mail.callout tone="danger" title="Why this fires">
            SkyLink's API reserves the seat but never issues the ticket; SkyLink issues it separately. Confirm the
            ticket with SkyLink and record the ticket number on the booking before the seat is released.
        </x-mail.callout>
    @else
        <x-mail.callout tone="danger" title="Why this fires">
            The airline returned CONFIRMED without a ticket number. Usually that is payment still settling, a
            ticketing-system delay, or a fare that needs issuing by hand. Check which before the fare expires.
        </x-mail.callout>
    @endif

</x-mail.layout>
