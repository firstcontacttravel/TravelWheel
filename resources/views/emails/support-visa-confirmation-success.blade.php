<x-mail.layout
    title="Visa confirmation request received"
    :preheader="'Payment received · reference '.$reference"
    eyebrow="Visa confirmation"
    heading="Your visa confirmation request is confirmed"
    :intro="'Hi '.($name ?? 'there').', we have received your payment and your request is now with our visa team.'"
    badge="Paid"
    tone="success"
>
    <x-mail.panel
        label="Reference"
        :value="$reference"
        mono
        alt-label="Amount paid"
        :alt-value="'NGN '.number_format((float) $amount, 2)"
    />

    <x-mail.callout title="What happens next">
        Our visa team will review your request and contact you with the confirmation documents. You do not need
        to take any further action right now.
    </x-mail.callout>
</x-mail.layout>
