<?php

namespace App\Services\Flights;

use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * What the airline needs of a booking's passengers beyond a filled-in form:
 * ages that match each passenger type on the travel date, child titles, and
 * passports that are present when the fare requires them and valid through
 * the trip. The website's checkout and the admin's booking both check here.
 */
class FlightPassengerRules
{
    /** The day the first flight leaves, which every age and passport date is measured against. */
    public function travelDate(array $flight, ?string $searchDepart = null): Carbon
    {
        $date = $flight['departDT'] ?? data_get($flight, 'segments.0.departDT');

        if (! $date && $searchDepart) {
            return Carbon::createFromFormat('d/m/Y', $searchDepart)->startOfDay();
        }

        return $date ? Carbon::parse($date)->startOfDay() : now()->startOfDay();
    }

    /** @throws ValidationException */
    public function validate(array $passengers, array $flight, Carbon $travelDate, string $prefix = 'passengers'): void
    {
        $passengers = collect($passengers);
        $messages = [];

        if ($passengers->count() > 9) {
            $messages[$prefix] = 'The total number of passengers must not exceed 9 per booking.';
        }

        $passportMandatory = filter_var($flight['isPassportMandatory'] ?? false, FILTER_VALIDATE_BOOLEAN);

        foreach ($passengers as $i => $pax) {
            $title = (string) ($pax['title'] ?? '');
            $type = (string) ($pax['type'] ?? '');

            if ($passportMandatory) {
                foreach ([
                    'passport_no' => 'Passport number',
                    'passport_issue_country' => 'Passport issuing country',
                    'passport_issue_date' => 'Passport issue date',
                    'passport_exp' => 'Passport expiry date',
                ] as $field => $label) {
                    if (blank($pax[$field] ?? null)) {
                        $messages["{$prefix}.{$i}.{$field}"] = "{$label} is required for this fare.";
                    }
                }
            }

            if (in_array($type, ['CHD', 'INF'], true) && ! in_array($title, ['Master', 'Miss'], true)) {
                $messages["{$prefix}.{$i}.title"] = 'Child and infant passenger titles must be Master or Miss.';
            }

            if (empty($pax['dob']) || empty($pax['type'])) {
                continue;
            }

            try {
                $age = Carbon::parse($pax['dob'])->diffInYears($travelDate);
            } catch (\Throwable) {
                continue;
            }

            $key = "{$prefix}.{$i}.dob";
            if ($pax['type'] === 'ADT' && $age < 18) {
                $messages[$key] = 'Adult passengers must be at least 18 years old on the travel date.';
            }
            if ($pax['type'] === 'CHD' && ($age < 2 || $age > 12)) {
                $messages[$key] = 'Child passengers must be between 2 and 12 years old on the travel date.';
            }
            if ($pax['type'] === 'INF' && $age >= 2) {
                $messages[$key] = 'Infant passengers must be under 2 years old on the travel date.';
            }

            if (! empty($pax['passport_issue_date'])) {
                try {
                    if (Carbon::parse($pax['passport_issue_date'])->gt($travelDate)) {
                        $messages["{$prefix}.{$i}.passport_issue_date"] = 'Passport issue date must be before the travel date.';
                    }
                } catch (\Throwable) {
                    // The request date rule provides the customer-facing error.
                }
            }
            if (! empty($pax['passport_exp'])) {
                try {
                    if (Carbon::parse($pax['passport_exp'])->lte($travelDate)) {
                        $messages["{$prefix}.{$i}.passport_exp"] = 'Passport must remain valid through the travel date.';
                    }
                } catch (\Throwable) {
                    // The request date rule provides the customer-facing error.
                }
            }
        }

        if (! empty($messages)) {
            throw ValidationException::withMessages($messages);
        }
    }
}
