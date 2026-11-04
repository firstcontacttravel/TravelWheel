<x-mail.layout
    :title="'Join '.$companyName.' on TravelWheel Corporate'"
    :preheader="'Your invitation to '.$companyName.' expires on '.$expiresOn"
    eyebrow="TravelWheel Corporate"
    heading="You're invited"
    :intro="($name ? 'Hi '.$name.', you' : 'You').' have been invited to '.$companyName.' on TravelWheel Corporate'.($isAdmin ? ' as its Company Admin.' : ' as a Booker.')"
>
    <x-mail.button :url="$url">Accept invitation</x-mail.button>
    <x-mail.link-fallback :url="$url" />

    <x-mail.callout title="Before you accept">
        This link works once and expires on {{ $expiresOn }}. It's only for you, so please don't forward it. If you
        weren't expecting it, you can ignore this email.
    </x-mail.callout>
</x-mail.layout>
