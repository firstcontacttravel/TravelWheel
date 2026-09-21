{{-- Internal — goes to the processing vendor, not the applicant. Carries the
     applicant's passport and contact details, so it stays a plain, dense
     record rather than a marketing-shaped email. --}}
@php
    $c = config('brand.colors');
    $font = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,Helvetica,sans-serif";
@endphp

<x-mail.layout
    :title="'Visa application '.$application->reference"
    :preheader="'For processing · '.$application->reference.' · '.($application->product?->name ?? 'Visa')"
    internal
    eyebrow="Internal · for processing"
    heading="Visa application for processing"
    intro="The applicant's uploaded documents are attached to this email."
    badge="Confidential"
    tone="danger"
>

    <x-mail.panel
        label="Application reference"
        :value="$application->reference"
        mono
        alt-label="Status"
        :alt-value="str($application->status)->headline()"
    />

    <x-mail.heading>Application</x-mail.heading>
    <x-mail.rows>
        <x-mail.row label="Visa product">{{ $application->product?->name ?? '—' }}</x-mail.row>
        <x-mail.row label="Destination">{{ data_get($application->search_snapshot, 'destination_name') ?: '—' }}</x-mail.row>
        <x-mail.row label="Arrival">{{ $application->arrival_date?->format('d M Y') ?: '—' }}</x-mail.row>
        <x-mail.row label="Departure">{{ $application->departure_date?->format('d M Y') ?: '—' }}</x-mail.row>
        <x-mail.row label="Contact email">{{ $application->contact_email ?: '—' }}</x-mail.row>
    </x-mail.rows>

    @foreach ($application->travelers as $traveler)
        <x-mail.heading>
            {{ ucfirst($traveler->traveler_type) }} — {{ trim("{$traveler->first_name} {$traveler->middle_name} {$traveler->last_name}") }}
        </x-mail.heading>
        <x-mail.rows>
            <x-mail.row label="Applicant profile">{{ str($traveler->applicant_type)->headline() }}</x-mail.row>
            <x-mail.row label="Title">{{ $traveler->title ?: '—' }}</x-mail.row>
            <x-mail.row label="Sex">{{ $traveler->sex ?: '—' }}</x-mail.row>
            <x-mail.row label="Date of birth">{{ $traveler->date_of_birth?->format('d M Y') ?: '—' }}</x-mail.row>
            <x-mail.row label="Place of birth">{{ $traveler->place_of_birth ?: '—' }}</x-mail.row>
            <x-mail.row label="Nationality">{{ $traveler->nationalityCountry?->name ?: '—' }}</x-mail.row>
            <x-mail.row label="Passport number" strong>{{ $traveler->passport_number ?: '—' }}</x-mail.row>
            <x-mail.row label="Passport type">{{ $traveler->passport_type ?: '—' }}</x-mail.row>
            <x-mail.row label="Issuing country">{{ $traveler->passportIssuingCountry?->name ?: '—' }}</x-mail.row>
            <x-mail.row label="Passport issued">{{ $traveler->passport_issued_at?->format('d M Y') ?: '—' }}</x-mail.row>
            <x-mail.row label="Passport expires">{{ $traveler->passport_expires_at?->format('d M Y') ?: '—' }}</x-mail.row>
            <x-mail.row label="Email">{{ $traveler->email ?: '—' }}</x-mail.row>
            <x-mail.row label="Phone">{{ $traveler->phone ?: '—' }}</x-mail.row>
            <x-mail.row label="Address">{{ $traveler->home_address ?: '—' }}</x-mail.row>
        </x-mail.rows>
    @endforeach

    @if ($application->answers->isNotEmpty())
        <x-mail.heading>Additional answers</x-mail.heading>
        <x-mail.rows>
            @foreach ($application->answers as $answer)
                <x-mail.row :label="$answer->question?->label ?? 'Question'">
                    {{ is_array($answer->value) ? implode(', ', $answer->value) : ($answer->value ?: '—') }}
                </x-mail.row>
            @endforeach
        </x-mail.rows>
    @endif

    <x-mail.callout tone="danger" title="Confidential">
        This message contains confidential personal information, including passport details. Use it only to
        process this visa application.
    </x-mail.callout>

</x-mail.layout>
