{{-- Internal — staff escalation, not a customer email. No passenger or payment details. --}}
@php
    $raiser = $escalation->raiser?->name ?? 'A colleague';
    $responder = $escalation->responder?->name ?? 'A colleague';
    $handoff = $escalation->mode === \App\Models\Escalation::MODE_HANDOFF;
    $when = $escalation->created_at?->timezone('Africa/Lagos')->format('D, d M Y \a\t H:i');
@endphp

@if ($event === 'resolved' || $event === 'declined')
    <x-mail.layout
        :title="ucfirst($event).': '.$reference"
        :preheader="$responder.' '.$event.' your escalation on '.$reference"
        internal
        eyebrow="Internal · {{ $service }}"
        :heading="$event === 'resolved' ? 'Your escalation was resolved' : 'Your escalation was declined'"
        :intro="$event === 'resolved'
            ? $responder.' has dealt with what you asked for on '.$reference.'. It is back with its owner.'
            : $responder.' could not take this on. '.$reference.' is still with its owner; escalate again or to someone else if it still needs help.'"
        :badge="ucfirst($event)"
        :tone="$event === 'resolved' ? 'success' : 'danger'"
    >
        <x-mail.rows>
            <x-mail.row label="Booking">{{ $reference }}</x-mail.row>
            <x-mail.row label="Stage">{{ $stage }}</x-mail.row>
            <x-mail.row label="You asked">{{ $escalation->reason }}</x-mail.row>
        </x-mail.rows>

        @if (filled($escalation->response_note))
            <x-mail.callout :tone="$event === 'resolved' ? 'success' : 'danger'" :title="$responder.' said'">{{ $escalation->response_note }}</x-mail.callout>
        @endif

        <x-mail.button :url="$adminUrl">Open {{ $reference }}</x-mail.button>
    </x-mail.layout>
@else
    <x-mail.layout
        :title="($handoff ? 'Hand-off: ' : 'Help needed: ').$reference"
        :preheader="$raiser.' escalated '.$reference.' to '.$escalation->targetLabel()"
        internal
        eyebrow="Internal · {{ $service }}"
        :heading="$handoff ? $raiser.' is handing '.$reference.' to you' : $raiser.' needs your help with '.$reference"
        :intro="$handoff
            ? 'Accept it in the admin to take it over. It becomes yours, in your queue.'
            : 'They keep the booking; you are being asked to sort out one thing and resolve it with a note.'"
        :badge="ucfirst($escalation->priority)"
        :tone="match ($escalation->priority) { 'urgent' => 'danger', 'high' => 'warning', default => 'neutral' }"
    >
        <x-mail.rows>
            <x-mail.row label="Booking">{{ $reference }}</x-mail.row>
            <x-mail.row label="Stage">{{ $stage }}</x-mail.row>
            <x-mail.row label="For">{{ $escalation->targetLabel() }}</x-mail.row>
            <x-mail.row label="Raised">{{ $when }}</x-mail.row>
        </x-mail.rows>

        <x-mail.callout :title="$raiser.' wrote'">{{ $escalation->reason }}</x-mail.callout>

        <x-mail.button :url="$adminUrl">Open {{ $reference }}</x-mail.button>
    </x-mail.layout>
@endif
