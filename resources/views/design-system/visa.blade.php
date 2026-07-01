<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visa Design System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/travelwheel-ui.css') }}">
</head>
<body>
    <x-ui.page-shell eyebrow="Phase 1 showcase" title="Visa application" copy="Reusable customer-facing components for the TravelWheel visa product.">
        <x-slot:progress>
            <x-ui.stepper :steps="['Trip', 'Travelers', 'Documents', 'Review']" :current="2" />
        </x-slot:progress>

        <x-ui.card title="Traveler details" description="Enter the details exactly as they appear on the passport.">
            <div class="tw-ui-stack">
                <x-ui.alert variant="info">All countries are available. Eligibility rules can include, exclude, or group nationalities.</x-ui.alert>

                <x-ui.field label="Passport nationality" for="nationality" required hint="Select the country shown on the passport.">
                    <x-ui.select id="nationality" name="nationality" aria-describedby="nationality-description">
                        <option value="">Choose a country</option>
                        <option>Nigeria</option>
                        <option>Ghana</option>
                        <option>Canada</option>
                    </x-ui.select>
                </x-ui.field>

                <x-ui.field label="Intended arrival" for="arrival" required>
                    <x-ui.date id="arrival" name="arrival" />
                </x-ui.field>

                <x-ui.field label="Travel notes" for="notes" hint="Optional details that affect this application.">
                    <x-ui.textarea id="notes" name="notes" rows="4">Business conference attendance.</x-ui.textarea>
                </x-ui.field>

                <x-ui.field label="Passport data page" for="passport" required hint="Upload a clear scan with all edges visible.">
                    <x-ui.file-upload id="passport" name="passport" />
                </x-ui.field>

                <div style="display:flex;flex-wrap:wrap;gap:10px">
                    <x-ui.button>Save and continue</x-ui.button>
                    <x-ui.button variant="outline" onclick="document.getElementById('requirements-modal').showModal()">Review requirements</x-ui.button>
                    <x-ui.badge variant="warning">Draft</x-ui.badge>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Application timeline">
            <x-ui.timeline :items="[
                ['title' => 'Application started', 'meta' => 'Today, 09:20', 'state' => 'complete'],
                ['title' => 'Traveler details', 'copy' => 'Complete identity and passport information.', 'state' => 'current'],
                ['title' => 'Document review', 'state' => 'upcoming'],
            ]" />
        </x-ui.card>

        <x-ui.state variant="empty" title="No additional requests" copy="Any document requests from the visa team will appear here." />

        <x-slot:summary>
            <x-ui.card title="Price summary" description="Your quote is valid for 30 minutes.">
                <x-ui.price-breakdown
                    :items="[
                        ['label' => 'Service fee', 'meta' => '1 adult', 'amount' => 50000],
                        ['label' => 'Document handling', 'amount' => 10000],
                    ]"
                    :direct-items="[['label' => 'Authority fee', 'amount' => 250000]]"
                    :total="60000"
                />
            </x-ui.card>
        </x-slot:summary>
    </x-ui.page-shell>

    <x-ui.modal id="requirements-modal" title="Before you continue">
        <p style="margin:0">Check that every document is current, legible, and matches the traveler.</p>
        <x-slot:footer>
            <x-ui.button variant="outline" onclick="this.closest('dialog').close()">Go back</x-ui.button>
            <x-ui.button onclick="this.closest('dialog').close()">Understood</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</body>
</html>
