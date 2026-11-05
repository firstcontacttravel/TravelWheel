<x-mail.layout
    :title="'Booking needs attention: '.$booking->booking_ref"
    :preheader="$booking->company->name.' · '.$booking->booking_ref"
    eyebrow="TravelWheel Corporate"
    heading="A booking needs attention"
    intro="You're the account manager for this company, and one of its bookings has run into a problem."
    :internal="true"
>
    <x-mail.rows>
        <x-mail.row label="Company">{{ $booking->company->name }}</x-mail.row>
        <x-mail.row label="Booking">{{ $booking->booking_ref }}</x-mail.row>
        <x-mail.row label="Route">{{ $booking->route }}</x-mail.row>
        <x-mail.row label="Status">{{ str($booking->booking_status)->replace('_', ' ')->title() }}</x-mail.row>
        <x-mail.row label="Payment">{{ str($booking->payment_status)->replace('_', ' ')->title() }}</x-mail.row>
    </x-mail.rows>

    <x-mail.button :url="$url">Open the booking</x-mail.button>
</x-mail.layout>
