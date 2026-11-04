<x-mail.layout
    :title="'Corporate request: '.$company->name"
    :preheader="$company->name.' asked for a corporate account'"
    eyebrow="TravelWheel Corporate"
    heading="New corporate account request"
    intro="A company has asked for a corporate account. Check its details, then approve or reject it in the admin."
    :internal="true"
>
    <x-mail.rows>
        <x-mail.row label="Company">{{ $company->name }}</x-mail.row>
        <x-mail.row label="CAC number">{{ $company->cac_number }}</x-mail.row>
        <x-mail.row label="Contact">{{ $company->contact_name }}</x-mail.row>
        <x-mail.row label="Email">{{ $company->contact_email }}</x-mail.row>
        <x-mail.row label="Phone">{{ $company->contact_phone ?: '-' }}</x-mail.row>
    </x-mail.rows>

    <x-mail.button :url="$url">Review the request</x-mail.button>
</x-mail.layout>
