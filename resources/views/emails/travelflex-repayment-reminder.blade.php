@php
    use App\Services\TravelFlexApplicationService as Reminders;

    $applicant = $application->applicant_details ?? [];
    $name = $applicant['full_name'] ?? 'Traveller';
    $amount = $instalment['amount'] ?? $instalment['total'] ?? $instalment['principal'] ?? 0;
    $dueDate = $instalment['dueDate'] ?? $instalment['due_date'] ?? $instalment['date'] ?? '—';
    $label = $instalment['label'] ?? 'TravelFlex repayment';
    $money = 'NGN '.number_format((float) $amount, 2);
    $stage = $stage ?? null;
    $isOverdue = in_array($stage, [Reminders::STAGE_OVERDUE_FIRST, Reminders::STAGE_OVERDUE_FINAL], true);
    $isFinal = $stage === Reminders::STAGE_OVERDUE_FINAL;

    $heading = match ($stage) {
        Reminders::STAGE_OVERDUE_FIRST => 'Your repayment is overdue',
        Reminders::STAGE_OVERDUE_FINAL => 'Your repayment is still overdue',
        Reminders::STAGE_DUE_TODAY => 'Your repayment is due today',
        Reminders::STAGE_DUE_TOMORROW => 'Your repayment is due tomorrow',
        default => 'Your next repayment is coming up',
    };
    $badge = match ($stage) {
        Reminders::STAGE_OVERDUE_FIRST => 'Overdue',
        Reminders::STAGE_OVERDUE_FINAL => 'Final reminder',
        Reminders::STAGE_DUE_TODAY => 'Due today',
        default => 'Due soon',
    };
    $sublabel = match ($stage) {
        Reminders::STAGE_OVERDUE_FIRST, Reminders::STAGE_OVERDUE_FINAL => 'overdue',
        Reminders::STAGE_DUE_TODAY => 'due today',
        Reminders::STAGE_DUE_TOMORROW => 'due tomorrow',
        Reminders::STAGE_DUE_IN_3_DAYS => 'due in 3 days',
        default => null,
    };
    $intro = $isOverdue
        ? 'Hi '.$name.', the instalment below was due on '.$dueDate.'. No action is needed if you have already paid it.'
        : 'Hi '.$name.', this is a reminder about the instalment below. No action is needed if you have already paid it.';
@endphp

<x-mail.layout
    :title="$label.' due '.$dueDate"
    :preheader="$money.' due '.$dueDate.' · booking '.$application->booking_ref"
    eyebrow="TravelFlex"
    :heading="$heading"
    :intro="$intro"
    :badge="$badge"
    tone="warning"
>
    <x-mail.panel
        :label="$label"
        :value="$money"
        alt-label="Due date"
        :alt-value="$dueDate"
        :alt-sublabel="$sublabel"
    />

    <x-mail.heading>Your plan</x-mail.heading>
    <x-mail.rows>
        <x-mail.row label="Booking reference">{{ $application->booking_ref }}</x-mail.row>
    </x-mail.rows>

    @if ($isFinal)
        <x-mail.callout title="This is our last automatic reminder for this instalment">
            Missing an instalment can affect your booking and future TravelFlex applications. Please pay it as soon as
            you can, or reply to this email so we can work it out with you.
        </x-mail.callout>
    @elseif ($isOverdue)
        <x-mail.callout title="Please pay as soon as you can">
            Missing an instalment can affect your booking and future TravelFlex applications. If something has changed
            and you cannot pay right now, reply to this email and we will work it out with you.
        </x-mail.callout>
    @else
        <x-mail.callout title="Paying on time keeps your trip on track">
            Missing an instalment can affect your booking and future TravelFlex applications. If something has changed
            and you cannot pay on {{ $dueDate }}, reply to this email before the due date and we will work it out with you.
        </x-mail.callout>
    @endif
</x-mail.layout>
