{{-- Internal — a missed deadline, not a customer email. No customer details. --}}
@php($ceo = $event === 'ceo')

<x-mail.layout
    :title="($ceo ? 'Still overdue: ' : 'Overdue: ').$reference"
    :preheader="$reference.' missed its deadline: '.$stage"
    internal
    eyebrow="Internal · {{ $service }}"
    :heading="$ceo ? $reference.' is still overdue' : $reference.' has missed its deadline'"
    :intro="$ceo
        ? 'It passed its due time a while ago and is still not done. You are being told because the team has not cleared it.'
        : ($owner
            ? $owner.' owns it. Anyone in '.($queue ?? 'the team').' can help or escalate it.'
            : 'Nobody owns it yet. Claim it in the admin, or escalate it if it is not yours to do.')"
    :badge="$ceo ? 'Still overdue' : 'Overdue'"
    tone="danger"
>
    <x-mail.rows>
        <x-mail.row label="Booking">{{ $reference }}</x-mail.row>
        <x-mail.row label="Step">{{ $stage }}</x-mail.row>
        <x-mail.row label="Was due">{{ $due }}</x-mail.row>
        <x-mail.row label="Owner">{{ $owner ?? 'Unclaimed' }}</x-mail.row>
        <x-mail.row label="Queue">{{ $queue ?? '-' }}</x-mail.row>
    </x-mail.rows>

    <x-mail.button :url="$adminUrl">Open {{ $reference }}</x-mail.button>
</x-mail.layout>
