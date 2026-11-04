<x-mail.layout
    title="Your sign-in code"
    :preheader="'This code expires in '.$minutes.' minutes.'"
    eyebrow="TravelWheel Corporate"
    heading="Your sign-in code"
    :intro="'Hi '.$name.', enter this code to finish signing in. It expires in '.$minutes.' minutes.'"
>
    <x-mail.panel label="Sign-in code" :value="$code" />

    <x-mail.callout title="Didn't try to sign in?" tone="warning">
        Someone may have your password. Please change it and tell your account manager at TravelWheel. Never share this
        code, including with TravelWheel staff.
    </x-mail.callout>
</x-mail.layout>
