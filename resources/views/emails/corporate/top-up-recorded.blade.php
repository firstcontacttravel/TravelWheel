<x-mail.layout
    :title="'Top-up to confirm: '.$topUp->company->name"
    :preheader="$topUp->company->name.' says it has sent a bank transfer'"
    eyebrow="TravelWheel Corporate"
    heading="Wallet top-up to confirm"
    intro="A company says it has sent a bank transfer. Check that the money has arrived, then confirm or reject it in the admin."
    :internal="true"
>
    <x-mail.rows>
        <x-mail.row label="Company">{{ $topUp->company->name }}</x-mail.row>
        <x-mail.row label="Amount">NGN {{ number_format((float) $topUp->amount, 2) }}</x-mail.row>
        <x-mail.row label="Our reference">{{ $topUp->reference }}</x-mail.row>
        <x-mail.row label="Their bank reference">{{ $topUp->bank_reference ?: '-' }}</x-mail.row>
    </x-mail.rows>

    <x-mail.button :url="$url">Open the queue</x-mail.button>
</x-mail.layout>
