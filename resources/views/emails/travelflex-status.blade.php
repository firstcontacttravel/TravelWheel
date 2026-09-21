@php
    $applicant = $application->applicant_details ?? [];
    $name = $applicant['full_name'] ?? 'Traveller';
    $plan = $application->repayment_plan ?? [];
    $feesTotal = (float) ($plan['administration_fee'] ?? 0) + (float) ($plan['insurance_fee'] ?? 0);
    $upfrontPaymentTotal = (float) ($plan['upfront_payment_total']
        ?? ((float) $application->down_payment + $feesTotal));
    $money = fn ($amount): string => $amount === null || $amount === ''
        ? '—'
        : 'NGN '.number_format((float) $amount, 2);

    $state = match ($status) {
        'approved' => ['label' => 'Approved', 'tone' => 'success', 'title' => 'Your TravelFlex application is approved'],
        'rejected' => ['label' => 'Not approved', 'tone' => 'danger', 'title' => 'An update on your TravelFlex application'],
        default => ['label' => 'Under review', 'tone' => 'neutral', 'title' => 'Your TravelFlex application is being reviewed'],
    };

    $deadlineLabel = $paymentDeadline?->timezone('Africa/Lagos')->format('D, d M Y \a\t H:i').' WAT';
@endphp

<x-mail.layout
    :title="$state['title']"
    :preheader="$state['label'].' · TravelFlex application for booking '.$application->booking_ref"
    eyebrow="TravelFlex"
    :heading="$state['title']"
    :intro="'Hi '.$name.', here is where your TravelFlex application stands.'"
    :badge="$state['label']"
    :tone="$state['tone']"
>

    <x-mail.panel
        label="Booking reference"
        :value="$application->booking_ref"
        mono
        alt-label="Total cost of travel"
        :alt-value="$money($application->grand_total)"
    />

    <x-mail.heading>Your plan</x-mail.heading>
    <x-mail.rows>
        <x-mail.row label="Payment 1 · Down payment">{{ $money($application->down_payment) }}</x-mail.row>
        <x-mail.row label="Payment 2 · Admin and insurance fees">{{ $money($feesTotal) }}</x-mail.row>
        <x-mail.row label="Total due now" strong>{{ $money($upfrontPaymentTotal) }}</x-mail.row>
        <x-mail.row label="Payment status">{{ str((string) $application->payment_status)->replace('_', ' ')->headline() }}</x-mail.row>
    </x-mail.rows>

    @if (filled($note))
        <x-mail.callout title="Note from our review team">{{ $note }}</x-mail.callout>
    @endif

    @if ($status === 'approved' && filled($paymentUrl))
        <x-mail.callout tone="warning" title="Your held fare expires {{ $deadlineLabel }}">
            Make both payments before then so we can issue your ticket: the down payment first, then the
            administration and insurance fees straight after, in the same visit.
        </x-mail.callout>

        <x-mail.button :url="$paymentUrl">Continue to payment</x-mail.button>
        <x-mail.link-fallback :url="$paymentUrl" />
    @elseif ($status === 'approved')
        <x-mail.callout tone="success" title="What happens next">
            Your application is approved. We will be in touch shortly with your payment link so we can issue
            your ticket.
        </x-mail.callout>
    @elseif ($status === 'rejected')
        <x-mail.callout tone="danger" title="What you can do next">
            We could not approve this application. If you would like to understand why, or look at other ways to
            pay for this trip, reply to this email and our team will help.
        </x-mail.callout>
    @else
        <x-mail.callout title="What happens next">
            Our team is reviewing your application. We will email you as soon as there is a decision — you do not
            need to do anything in the meantime.
        </x-mail.callout>
    @endif

</x-mail.layout>
