@php $c = config('brand.colors'); @endphp
<x-mail.layout
    title="Your visa portal access code"
    preheader="Your one-time code expires in 10 minutes"
    eyebrow="Visa portal"
    heading="Your access code"
    :intro="'Use the code below to open application '.$application->reference.'.'"
>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid {{ $c['line'] }};border-radius:10px;background-color:{{ $c['panel'] }};margin-bottom:20px;">
    <tr><td align="center" style="padding:24px 18px;">
        <div style="font-family:'SFMono-Regular',Consolas,Menlo,Courier,monospace;font-size:32px;font-weight:700;letter-spacing:8px;color:{{ $c['brand'] }};">{{ $code }}</div>
    </td></tr>
    </table>

    <x-mail.callout tone="warning" title="This code expires in 10 minutes">
        If you did not request it, you can ignore this email — nobody can access your application without it.
        We will never ask you for this code by phone or message.
    </x-mail.callout>
</x-mail.layout>
