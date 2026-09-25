<x-mail.layout
    :title="$heading"
    :preheader="$heading.' · application '.$application->reference"
    eyebrow="Visa application"
    :heading="$heading"
    :intro="$bodyText"
>
    <x-mail.panel label="Application reference" :value="$application->reference" mono />

    <x-mail.button :url="route('visa.portal.entry')">Open your application</x-mail.button>
    <x-mail.link-fallback :url="route('visa.portal.entry')" />

    <x-mail.callout>
        For your security the portal will email you a one-time code to confirm it is you before showing your
        application.
    </x-mail.callout>
</x-mail.layout>
