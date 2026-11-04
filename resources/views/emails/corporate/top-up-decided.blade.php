@php($confirmed = $topUp->status === 'confirmed')
<x-mail.layout
    :title="$confirmed ? 'Wallet topped up' : 'Top-up not confirmed'"
    :preheader="'Top-up '.$topUp->reference"
    eyebrow="TravelWheel Corporate"
    :heading="$confirmed ? 'Your wallet has been topped up' : 'Your top-up was not confirmed'"
    :intro="$confirmed
        ? 'We have added the amount below to '.$topUp->company->name.'\'s wallet.'
        : 'We could not confirm this top-up for '.$topUp->company->name.', so nothing has been added to the wallet.'"
    :badge="$confirmed ? 'Confirmed' : 'Not confirmed'"
    :tone="$confirmed ? 'success' : 'warning'"
>
    <x-mail.rows>
        <x-mail.row label="Reference">{{ $topUp->reference }}</x-mail.row>
        <x-mail.row label="Amount">NGN {{ number_format((float) $topUp->amount, 2) }}</x-mail.row>
        @if ($topUp->isCard())
            <x-mail.row label="Card fee">NGN {{ number_format((float) $topUp->fee_amount, 2) }}</x-mail.row>
            <x-mail.row label="Total charged">NGN {{ number_format((float) $topUp->total_charged, 2) }}</x-mail.row>
        @endif
        @if ($confirmed)
            <x-mail.row label="Wallet balance" :strong="true">NGN {{ number_format($balance, 2) }}</x-mail.row>
        @elseif ($topUp->decision_note)
            <x-mail.row label="Reason">{{ $topUp->decision_note }}</x-mail.row>
        @endif
    </x-mail.rows>

    <x-mail.button :url="$url">View your wallet</x-mail.button>
</x-mail.layout>
