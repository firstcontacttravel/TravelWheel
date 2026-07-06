<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Visa application {{ $application->reference }}</title></head>
<body style="font-family:Arial,sans-serif;color:#14213d;line-height:1.5">
    <h1 style="font-size:22px">Visa application {{ $application->reference }}</h1>
    <p>TravelWheel has sent this application for processing. The applicant's uploaded documents are attached to this email.</p>

    <h2 style="font-size:17px">Application</h2>
    <table cellpadding="6" cellspacing="0" border="1" style="border-collapse:collapse;border-color:#d8dee9">
        <tr><th align="left">Visa product</th><td>{{ $application->product?->name }}</td></tr>
        <tr><th align="left">Status</th><td>{{ str($application->status)->headline() }}</td></tr>
        <tr><th align="left">Destination</th><td>{{ data_get($application->search_snapshot, 'destination_name') }}</td></tr>
        <tr><th align="left">Arrival</th><td>{{ $application->arrival_date?->format('d M Y') }}</td></tr>
        <tr><th align="left">Departure</th><td>{{ $application->departure_date?->format('d M Y') }}</td></tr>
        <tr><th align="left">Contact email</th><td>{{ $application->contact_email }}</td></tr>
    </table>

    @foreach($application->travelers as $traveler)
        <h2 style="font-size:17px">{{ ucfirst($traveler->traveler_type) }}: {{ trim("{$traveler->first_name} {$traveler->middle_name} {$traveler->last_name}") }}</h2>
        <table cellpadding="6" cellspacing="0" border="1" style="border-collapse:collapse;border-color:#d8dee9">
            <tr><th align="left">Applicant profile</th><td>{{ str($traveler->applicant_type)->headline() }}</td></tr>
            <tr><th align="left">Title</th><td>{{ $traveler->title }}</td></tr>
            <tr><th align="left">Sex</th><td>{{ $traveler->sex }}</td></tr>
            <tr><th align="left">Date of birth</th><td>{{ $traveler->date_of_birth?->format('d M Y') }}</td></tr>
            <tr><th align="left">Place of birth</th><td>{{ $traveler->place_of_birth }}</td></tr>
            <tr><th align="left">Nationality</th><td>{{ $traveler->nationalityCountry?->name }}</td></tr>
            <tr><th align="left">Passport number</th><td>{{ $traveler->passport_number }}</td></tr>
            <tr><th align="left">Passport type</th><td>{{ $traveler->passport_type }}</td></tr>
            <tr><th align="left">Passport issuing country</th><td>{{ $traveler->passportIssuingCountry?->name }}</td></tr>
            <tr><th align="left">Passport issued</th><td>{{ $traveler->passport_issued_at?->format('d M Y') }}</td></tr>
            <tr><th align="left">Passport expires</th><td>{{ $traveler->passport_expires_at?->format('d M Y') }}</td></tr>
            <tr><th align="left">Email</th><td>{{ $traveler->email }}</td></tr>
            <tr><th align="left">Phone</th><td>{{ $traveler->phone }}</td></tr>
            <tr><th align="left">Address</th><td>{{ $traveler->home_address }}</td></tr>
        </table>
    @endforeach

    @if($application->answers->isNotEmpty())
        <h2 style="font-size:17px">Additional answers</h2>
        <table cellpadding="6" cellspacing="0" border="1" style="border-collapse:collapse;border-color:#d8dee9">
            @foreach($application->answers as $answer)
                <tr><th align="left">{{ $answer->question?->label }}</th><td>{{ is_array($answer->value) ? implode(', ', $answer->value) : $answer->value }}</td></tr>
            @endforeach
        </table>
    @endif

    <p style="margin-top:24px;color:#52606d;font-size:13px">This message contains confidential personal information. Use it only to process this visa application.</p>
</body>
</html>
