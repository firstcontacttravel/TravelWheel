{{-- Internal — flight ops alert, not a customer email. --}}
@php
    $details = $event->details ?? [];
    $when = $event->created_at?->timezone('Africa/Lagos')->format('D, d M Y \a\t H:i');
@endphp

<x-mail.layout
    :title="$paused ? $label.' paused' : $label.' resumed'"
    :preheader="$paused ? $label.' was paused automatically' : $label.' is back in searches'"
    internal
    eyebrow="Internal · flight APIs"
    :heading="$paused ? $label.' has been paused automatically' : $label.' is back in searches'"
    :intro="$paused
        ? 'Too many of its recent searches failed, so customers are not being sent to it for now. Other switched-on APIs carry on as normal.'
        : 'Its searches are succeeding again after the automatic pause, and customers are being sent to it again.'"
    :badge="$paused ? 'Paused' : 'Resumed'"
    :tone="$paused ? 'danger' : 'success'"
>

    <x-mail.rows>
        <x-mail.row label="API">{{ $label }}</x-mail.row>
        <x-mail.row label="When">{{ $when }}</x-mail.row>
        @if ($paused)
            <x-mail.row label="Failures">{{ $details['failed'] ?? '—' }} of {{ $details['calls'] ?? '—' }} recent searches and price checks</x-mail.row>
            <x-mail.row label="Paused for">{{ $details['pause_minutes'] ?? '—' }} minutes, then tried again</x-mail.row>
        @endif
    </x-mail.rows>

    @if ($paused && filled($event->reason))
        <x-mail.callout tone="danger" title="What was seen">{{ $event->reason }}</x-mail.callout>
    @elseif (! $paused && filled($details['was_paused_for'] ?? null))
        <x-mail.callout tone="success" title="It had been paused because">{{ $details['was_paused_for'] }}</x-mail.callout>
    @endif

    <x-mail.button :url="$adminUrl">Open Flight APIs</x-mail.button>

    @if ($paused)
        <x-mail.callout title="Nothing to do if it recovers">
            After the pause it is tried again on its own and resumes once searches succeed — you will get a
            second email. To take it out for longer, switch it off on the Flight APIs screen.
        </x-mail.callout>
    @endif

</x-mail.layout>
