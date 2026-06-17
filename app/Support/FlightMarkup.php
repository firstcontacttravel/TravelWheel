<?php

namespace App\Support;

class FlightMarkup
{
    private const NIGERIA = 'nigeria';

    private const MARKUPS = [
        'from_nigeria' => [
            'economy' => 30000.0,
            'premium_economy' => 60000.0,
            'business' => 100000.0,
            'first' => 150000.0,
        ],
        'touches_nigeria' => [
            'economy' => 70000.0,
            'premium_economy' => 120000.0,
            'business' => 200000.0,
            'first' => 300000.0,
        ],
        'not_nigeria' => [
            'economy' => 100000.0,
            'premium_economy' => 170000.0,
            'business' => 240000.0,
            'first' => 350000.0,
        ],
    ];

    public static function apply(array $flight): array
    {
        $supplierPrice = (float) ($flight['supplierPrice'] ?? $flight['price'] ?? 0);
        $category = self::routeCategory($flight);
        $cabin = self::cabinCategory($flight);
        $markup = self::MARKUPS[$category][$cabin] ?? 0.0;

        $flight['supplierPrice'] = round($supplierPrice, 2);
        $flight['markupAmount'] = round($markup, 2);
        $flight['markupCategory'] = $category;
        $flight['markupCabin'] = $cabin;
        $flight['price'] = round($supplierPrice + $markup, 2);

        return $flight;
    }

    public static function routeCategory(array $flight): string
    {
        $legs = self::segments($flight);
        $first = $legs[0] ?? [];

        $originNigeria = self::isNigeria($first['fromCountry'] ?? null);
        $firstDestinationNigeria = self::isNigeria($first['toCountry'] ?? null);

        if ($originNigeria && ! $firstDestinationNigeria) {
            return 'from_nigeria';
        }

        foreach ($legs as $segment) {
            if (self::isNigeria($segment['fromCountry'] ?? null) || self::isNigeria($segment['toCountry'] ?? null)) {
                return 'touches_nigeria';
            }
        }

        return 'not_nigeria';
    }

    public static function cabinCategory(array $flight): string
    {
        $value = strtolower(trim((string) (
            $flight['cabinCode']
            ?? data_get($flight, 'segments.0.cabinCode')
            ?? data_get($flight, 'multiLegs.0.segments.0.cabinCode')
            ?? $flight['cabin']
            ?? data_get($flight, 'segments.0.cabin')
            ?? data_get($flight, 'multiLegs.0.segments.0.cabin')
            ?? 'Y'
        )));

        return match ($value) {
            's', 'w', 'premiumeconomy', 'premium economy', 'premium_economy' => 'premium_economy',
            'c', 'j', 'biz', 'business' => 'business',
            'f', 'p', 'first', 'firstclass', 'first class' => 'first',
            default => 'economy',
        };
    }

    private static function segments(array $flight): array
    {
        $segments = [];

        foreach (($flight['segments'] ?? []) as $segment) {
            if (is_array($segment)) {
                $segments[] = $segment;
            }
        }

        foreach (($flight['returnSegments'] ?? []) as $segment) {
            if (is_array($segment)) {
                $segments[] = $segment;
            }
        }

        foreach (($flight['multiLegs'] ?? []) as $leg) {
            foreach (($leg['segments'] ?? []) as $segment) {
                if (is_array($segment)) {
                    $segments[] = $segment;
                }
            }
        }

        return $segments;
    }

    private static function isNigeria(mixed $country): bool
    {
        return strtolower(trim((string) $country)) === self::NIGERIA;
    }
}
