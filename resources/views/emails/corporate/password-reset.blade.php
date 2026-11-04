<x-mail.layout
    title="Reset your password"
    :preheader="'This link expires in '.$minutes.' minutes.'"
    eyebrow="TravelWheel Corporate"
    heading="Reset your password"
    :intro="'Hi '.$name.', use the button below to choose a new password. The link expires in '.$minutes.' minutes.'"
>
    <x-mail.button :url="$url">Choose a new password</x-mail.button>
    <x-mail.link-fallback :url="$url" />

    <x-mail.callout title="Didn't ask for this?">
        You can ignore this email. Your password won't change.
    </x-mail.callout>
</x-mail.layout>
