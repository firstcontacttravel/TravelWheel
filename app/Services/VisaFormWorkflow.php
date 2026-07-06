<?php

namespace App\Services;

class VisaFormWorkflow
{
    public const TRAVELER_FIELDS = [
        'applicant_type' => 'Application profile / parent type',
        'first_name' => 'First name',
        'middle_name' => 'Middle name',
        'last_name' => 'Last name',
        'sex' => 'Sex',
        'date_of_birth' => 'Date of birth',
        'place_of_birth' => 'Place of birth',
        'nationality_country_id' => 'Nationality',
        'email' => 'Email address',
        'phone' => 'Phone number',
        'home_address' => 'Home address',
    ];

    public const PASSPORT_FIELDS = [
        'passport_number' => 'Passport number',
        'passport_type' => 'Passport type',
        'passport_issued_at' => 'Passport issue date',
        'passport_expires_at' => 'Passport expiry date',
        'passport_issuing_country_id' => 'Passport issuing country',
    ];

    public function defaults(): array
    {
        return [
            'traveler_fields' => array_keys(self::TRAVELER_FIELDS),
            'passport_fields' => array_keys(self::PASSPORT_FIELDS),
        ];
    }

    public function normalize(?array $configuration): array
    {
        if ($this->isLegacyStepConfiguration($configuration)) {
            $steps = collect($configuration)->keyBy('key');

            return [
                'traveler_fields' => ($steps->get('travelers')['enabled'] ?? true) ? array_keys(self::TRAVELER_FIELDS) : [],
                'passport_fields' => ($steps->get('passports')['enabled'] ?? true) ? array_keys(self::PASSPORT_FIELDS) : [],
            ];
        }

        $configuration ??= [];

        return [
            'traveler_fields' => $this->validFields($configuration['traveler_fields'] ?? array_keys(self::TRAVELER_FIELDS), self::TRAVELER_FIELDS),
            'passport_fields' => $this->validFields($configuration['passport_fields'] ?? array_keys(self::PASSPORT_FIELDS), self::PASSPORT_FIELDS),
            'steps' => array_intersect_key((array) ($configuration['steps'] ?? []), array_flip(['questions', 'services', 'documents', 'hasQuestions', 'hasServices', 'hasDocuments'])),
        ];
    }

    public function snapshot(?array $configuration, bool $hasQuestions, bool $hasServices, bool $hasDocuments): array
    {
        return array_replace($this->normalize($configuration), [
            'steps' => compact('hasQuestions', 'hasServices', 'hasDocuments'),
        ]);
    }

    public function applicationFlow(?array $configuration, bool $hasQuestions = false, bool $hasServices = false, bool $hasDocuments = false): array
    {
        $configuration = $this->normalize($configuration);
        $snapshotSteps = $configuration['steps'] ?? [];
        $hasQuestions = (bool) ($snapshotSteps['hasQuestions'] ?? $snapshotSteps['questions'] ?? $hasQuestions);
        $hasServices = (bool) ($snapshotSteps['hasServices'] ?? $snapshotSteps['services'] ?? $hasServices);
        $hasDocuments = (bool) ($snapshotSteps['hasDocuments'] ?? $snapshotSteps['documents'] ?? $hasDocuments);

        $steps = [$this->step('trip', 'Trip details', 'Confirm your trip, contact email, and processing option.')];
        if ($configuration['traveler_fields'] !== []) {
            $steps[] = $this->step('travelers', 'Travelers', 'Enter the traveler information required for this visa.');
        }
        if ($configuration['passport_fields'] !== []) {
            $steps[] = $this->step('passports', 'Passports', 'Enter the passport information required for this visa.');
        }
        if ($hasQuestions) {
            $steps[] = $this->step('questions', 'Questions', 'Answer the additional questions required for this visa.');
        }
        if ($hasServices) {
            $steps[] = $this->step('services', 'Services', 'Choose any optional TravelWheel services you need.');
        }
        if ($hasDocuments) {
            $steps[] = $this->step('documents', 'Documents', 'Upload the documents required for this application.');
        }
        $steps[] = $this->step('review', 'Review', 'Review your application and confirm that the information is accurate.');
        $steps[] = $this->step('payment', 'Payment', 'Generate your quotation and pay securely.');

        return $steps;
    }

    private function isLegacyStepConfiguration(?array $configuration): bool
    {
        return is_array($configuration) && array_is_list($configuration) && isset($configuration[0]['key']);
    }

    private function validFields(mixed $selected, array $available): array
    {
        return array_values(array_intersect((array) $selected, array_keys($available)));
    }

    private function step(string $key, string $title, string $description): array
    {
        return compact('key', 'title', 'description');
    }
}
