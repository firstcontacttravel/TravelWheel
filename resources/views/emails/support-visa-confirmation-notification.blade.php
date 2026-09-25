{{-- Internal — goes to the support desk, not the customer. --}}
<x-mail.layout
    title="New visa confirmation request"
    :preheader="'Paid · '.$name.' · '.$reference"
    internal
    eyebrow="Internal · support desk"
    heading="New visa confirmation request"
    intro="Payment is confirmed. Details below for processing."
    badge="Action needed"
    tone="warning"
>
    <x-mail.panel
        label="Payment reference"
        :value="$reference"
        mono
        alt-label="Amount paid"
        :alt-value="'NGN '.number_format((float) $amount, 2)"
    />

    <x-mail.heading>Client</x-mail.heading>
    <x-mail.rows>
        <x-mail.row label="Name">{{ $name }}</x-mail.row>
        <x-mail.row label="Email">{{ $email }}</x-mail.row>
        <x-mail.row label="Phone">{{ $phone }}</x-mail.row>
    </x-mail.rows>

    <x-mail.heading>Request</x-mail.heading>
    <x-mail.rows>
        <x-mail.row label="Service">{{ $service }}</x-mail.row>
        <x-mail.row label="Booked through">{{ $booking_source }}</x-mail.row>
    </x-mail.rows>

    @if (filled($additional_info ?? null))
        <x-mail.callout title="Additional information from the client">{{ $additional_info }}</x-mail.callout>
    @endif
</x-mail.layout>
