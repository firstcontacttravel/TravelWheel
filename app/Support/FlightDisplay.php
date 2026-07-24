<?php

namespace App\Support;

class FlightDisplay
{
    private const CABIN_BY_CODE = [
        'Y' => 'Economy',
        'S' => 'Premium Economy',
        'W' => 'Premium Economy',
        'C' => 'Business',
        'J' => 'Business',
        'F' => 'First Class',
        'P' => 'First Class',
    ];

    public static function cabin(mixed $flight = null, mixed $booking = null): string
    {
        $values = [
            data_get($booking, 'cabin'),
            data_get($flight, 'cabin'),
            data_get($booking, 'flight_snapshot.cabin'),
            data_get($flight, 'segments.0.cabin'),
            data_get($booking, 'flight_snapshot.segments.0.cabin'),
        ];

        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }

        $code = strtoupper(trim((string) (
            data_get($booking, 'cabinCode')
            ?: data_get($flight, 'cabinCode')
            ?: data_get($booking, 'flight_snapshot.cabinCode')
            ?: data_get($flight, 'segments.0.cabinCode')
            ?: data_get($booking, 'flight_snapshot.segments.0.cabinCode')
            ?: session('bookingSearchParams.flight_type', session('searchParamsStore.flight_type', 'Y'))
        )));

        return self::CABIN_BY_CODE[$code] ?? 'Economy';
    }

    public static function passengers(mixed $passengers): array
    {
        return collect($passengers ?? [])
            ->map(function ($passenger) {
                $pax = is_object($passenger) ? (array) $passenger : (array) $passenger;

                return array_merge($pax, [
                    'title'       => self::first($pax, ['title', 'PassengerTitle', 'Title']),
                    'first_name'  => self::first($pax, ['first_name', 'PassengerFirstName', 'FirstName', 'given_name']),
                    'last_name'   => self::first($pax, ['last_name', 'PassengerLastName', 'LastName', 'surname']),
                    'type'        => self::first($pax, ['type', 'PassengerType', 'passengerType'], 'ADT'),
                    'dob'         => self::first($pax, ['dob', 'DateOfBirth', 'PassengerDateOfBirth', 'BirthDate']),
                    'nationality' => self::first($pax, ['nationality', 'PassengerNationality', 'Nationality']),
                    'passport_no' => self::first($pax, ['passport_no', 'PassportNumber', 'PassportNo', 'Passport']),
                    'email'       => self::first($pax, ['email', 'EmailAddress', 'PassengerEmail']),
                    'phone'       => self::first($pax, ['phone', 'PhoneNumber', 'PassengerPhone']),
                ]);
            })
            ->values()
            ->toArray();
    }

    public static function route(mixed $flight): string
    {
        $groups = [];
        $multiLegs = data_get($flight, 'multiLegs', []);

        if (is_array($multiLegs) && $multiLegs !== []) {
            foreach ($multiLegs as $leg) {
                $groups[] = data_get($leg, 'segments', []);
            }
        } else {
            $groups[] = data_get($flight, 'segments', []);

            $returnSegments = data_get($flight, 'returnSegments', []);
            if (is_array($returnSegments) && $returnSegments !== []) {
                $groups[] = $returnSegments;
            }
        }

        $codes = [];

        foreach ($groups as $segments) {
            if (! is_array($segments)) {
                continue;
            }

            foreach ($segments as $segment) {
                $segment = is_object($segment) ? (array) $segment : (array) $segment;
                $from = strtoupper(trim(self::first($segment, [
                    'from',
                    'fromCode',
                    'airportOriginCode',
                    'DepartureAirportLocationCode',
                ])));
                $to = strtoupper(trim(self::first($segment, [
                    'to',
                    'toCode',
                    'airportDestinationCode',
                    'ArrivalAirportLocationCode',
                ])));

                if ($from !== '' && end($codes) !== $from) {
                    $codes[] = $from;
                }

                if ($to !== '' && end($codes) !== $to) {
                    $codes[] = $to;
                }
            }
        }

        if ($codes === []) {
            $from = strtoupper(trim((string) data_get($flight, 'from', '')));
            $to = strtoupper(trim((string) data_get($flight, 'to', '')));
            $codes = array_values(array_filter([$from, $to]));
        }

        return implode(' → ', $codes);
    }

    public static function cabinBaggageValues(mixed $values): array
    {
        return collect(is_array($values) ? $values : [$values])
            ->map(fn ($value): string => self::cabinBaggageLabel($value))
            ->filter()
            ->values()
            ->all();
    }

    public static function cabinBaggageLabel(mixed $value): string
    {
        $label = trim((string) $value);

        return strtoupper($label) === 'SB' ? '7KG' : $label;
    }

    private static function first(array $data, array $keys, string $default = ''): string
    {
        foreach ($keys as $key) {
            $value = data_get($data, $key);
            if ($value !== null && trim((string) $value) !== '') {
                return (string) $value;
            }
        }

        return $default;
    }
}
