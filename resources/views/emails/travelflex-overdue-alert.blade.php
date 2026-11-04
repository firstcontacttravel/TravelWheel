{{-- To Fast Credit, copied to TravelWheel. Contact details and the instalment only: no BVN, bank details or documents. --}}
@php($money = 'NGN '.number_format($amount, 2))

<x-mail.layout
    :title="'TravelFlex repayment overdue — '.$bookingRef"
    :preheader="$money.' overdue · booking '.$bookingRef"
    eyebrow="TravelFlex · Repayment alert"
    :heading="$bookingRef.' has an overdue repayment'"
    intro="This instalment is still unpaid in TravelWheel's records and the customer has had their last automatic reminder, so it now needs someone to follow up."
    badge="Overdue"
    tone="danger"
>
    <x-mail.panel
        :label="$label"
        :value="$money"
        alt-label="Was due"
        :alt-value="$dueLabel"
        :alt-sublabel="$daysOverdue !== null ? $daysOverdue.' days overdue' : null"
    />

    <x-mail.heading>Customer</x-mail.heading>
    <x-mail.rows>
        <x-mail.row label="Name">{{ $customerName }}</x-mail.row>
        <x-mail.row label="Email">{{ $customerEmail }}</x-mail.row>
        <x-mail.row label="Phone">{{ $customerPhone }}</x-mail.row>
    </x-mail.rows>

    <x-mail.heading>Plan</x-mail.heading>
    <x-mail.rows>
        <x-mail.row label="Booking reference">{{ $bookingRef }}</x-mail.row>
        <x-mail.row label="Instalments paid">{{ $paidCount }} of {{ $totalCount }}</x-mail.row>
    </x-mail.rows>

    <x-mail.button :url="$adminUrl">Open in TravelWheel admin (TravelWheel staff)</x-mail.button>
</x-mail.layout>
