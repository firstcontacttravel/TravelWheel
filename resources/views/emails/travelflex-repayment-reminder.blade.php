@php
    $applicant = $application->applicant_details ?? [];
    $name = $applicant['full_name'] ?? 'Traveller';
    $amount = $instalment['amount'] ?? $instalment['total'] ?? $instalment['principal'] ?? 0;
    $dueDate = $instalment['dueDate'] ?? $instalment['due_date'] ?? $instalment['date'] ?? '—';
    $label = $instalment['label'] ?? 'TravelFlex repayment';
    $money = 'NGN '.number_format((float) $amount, 2);
@endphp

<x-mail.layout
    :title="$label.' due '.$dueDate"
    :preheader="$money.' due '.$dueDate.' · booking '.$application->booking_ref"
    eyebrow="TravelFlex"
    heading="Your next repayment is coming up"
    :intro="'Hi '.$name.', this is a reminder about the instalment below. No action is needed if you have already paid it.'"
    badge="Due soon"
    tone="warning"
>
    <x-mail.panel
        :label="$label"
        :value="$money"
        alt-label="Due date"
        :alt-value="$dueDate"
        :alt-sublabel="$timing ?? null"
    />

    <x-mail.heading>Your plan</x-mail.heading>
    <x-mail.rows>
        <x-mail.row label="Booking reference">{{ $application->booking_ref }}</x-mail.row>
    </x-mail.rows>

    <x-mail.callout title="Paying on time keeps your trip on track">
        Missing an instalment can affect your booking and future TravelFlex applications. If something has changed
        and you cannot pay on {{ $dueDate }}, reply to this email before the due date and we will work it out with you.
    </x-mail.callout>
</x-mail.layout>
