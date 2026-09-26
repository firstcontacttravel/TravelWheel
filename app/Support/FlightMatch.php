<?php

namespace App\Support;

/**
 * Recognises the same physical flight across suppliers.
 *
 * Two offers match when every leg — outbound, return and each multi-city
 * leg, in order — is the same airline and flight number leaving at the same
 * local minute, in the same cabin. Exact on purpose: a missed match only
 * shows two cards where one would do, while a false match could hide a
 * genuinely different, cheaper flight.
 *
 * The suppliers describe the same flight differently, which is why this is
 * normalised rather than compared field by field:
 *   - departure: TravelNext sends "2026-10-20T10:30:00", SkyLink's mapper
 *     produces "2026-10-20T10:30:00+00:00" for the same local time — only the
 *     local date and minute are compared;
 *   - flight number: "BA75", "BA075", "BA 75" and "75" under airline BA are
 *     one flight;
 *   - cabin: SkyLink's cabinCode is the booking-class letter (e.g. "O"), not
 *     the cabin, so the cabin NAME is preferred and the code is only a
 *     fallback.
 *
 * The results page dedupes on this key (flight.matchKey), and select() uses
 * it to offer the same flight from another API when the chosen one is off.
 */
class FlightMatch
{
    public static function key(array $flight): string
    {
        $legs = array_map(fn (array $segment): string => self::segmentKey($segment), self::segments($flight));

        return implode('|', $legs).'#'.self::cabin($flight);
    }

    /** Adds matchKey to each flight, for the results page's dedupe. */
    public static function tag(array $flights): array
    {
        return array_map(fn (array $flight): array => $flight + ['matchKey' => self::key($flight)], $flights);
    }

    private static function segmentKey(array $segment): string
    {
        $airline = strtoupper(trim((string) ($segment['airlineCode'] ?? '')));
        $number = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) ($segment['flightNo'] ?? '')));

        if ($airline !== '' && str_starts_with($number, $airline)) {
            $number = substr($number, strlen($airline));
        }

        $number = ltrim($number, '0');
        $departure = substr(str_replace(' ', 'T', (string) ($segment['departDT'] ?? '')), 0, 16);

        return $airline.($number === '' ? '0' : $number).'@'.$departure;
    }

    private static function segments(array $flight): array
    {
        $segments = [];

        foreach (['segments', 'returnSegments'] as $group) {
            foreach ((array) ($flight[$group] ?? []) as $segment) {
                if (is_array($segment)) {
                    $segments[] = $segment;
                }
            }
        }

        foreach ((array) ($flight['multiLegs'] ?? []) as $leg) {
            foreach ((array) ($leg['segments'] ?? []) as $segment) {
                if (is_array($segment)) {
                    $segments[] = $segment;
                }
            }
        }

        return $segments;
    }

    private static function cabin(array $flight): string
    {
        $name = strtolower(trim((string) (
            ($flight['cabin'] ?? null)
            ?: data_get($flight, 'segments.0.cabin')
            ?: data_get($flight, 'multiLegs.0.segments.0.cabin')
            ?: ''
        )));
        $name = str_replace(['_', '-'], ' ', $name);

        if ($name !== '') {
            return match (true) {
                str_contains($name, 'premium') => 'premium_economy',
                str_contains($name, 'business') => 'business',
                str_contains($name, 'first') => 'first',
                default => 'economy',
            };
        }

        return FlightMarkup::cabinCategory($flight);
    }
}
