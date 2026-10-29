<?php

namespace App\Support;

use App\Models\FleetCar;

/**
 * The states Car Hire and Pick up 'n' Drop off can run in, from
 * config('carhire.states'). A state is open for booking once it has at
 * least one active fleet car, so admin launches a state by adding a car
 * to it; no code or config change is needed.
 */
class GroundStates
{
    /** ['South West' => ['lagos' => 'Lagos', ...], ...] for grouped pickers. */
    public static function grouped(): array
    {
        return collect(config('carhire.states', []))
            ->map(fn (array $states): array => collect($states)->map(fn (array $s): string => $s['name'])->all())
            ->all();
    }

    /** ['lagos' => 'Lagos', 'abuja' => 'FCT Abuja', ...] */
    public static function all(): array
    {
        return array_merge(...array_values(self::grouped()) ?: [[]]);
    }

    public static function name(?string $key): string
    {
        return self::all()[$key] ?? ucwords(str_replace('_', ' ', (string) $key));
    }

    public static function example(?string $key): string
    {
        $state = self::config($key);

        return $state ? ($state['example'] ?? 'e.g. street, area, '.$state['name']) : 'Enter the full address';
    }

    public static function airportExample(?string $key): string
    {
        return self::config($key)['airport'] ?? 'e.g. the airport, or your hotel';
    }

    private static function config(?string $key): ?array
    {
        foreach (config('carhire.states', []) as $states) {
            if (isset($states[$key])) {
                return $states[$key];
            }
        }

        return null;
    }

    public static function default(): string
    {
        return (string) config('carhire.default_state', 'lagos');
    }

    /**
     * States with at least one active fleet car, in picker order. Pass a
     * service ('car_hire' / 'transfer') to ask about one service only.
     */
    public static function open(?string $service = null): array
    {
        $withCars = FleetCar::active()
            ->when($service, fn ($q) => $q->where('service_type', $service))
            ->distinct()
            ->pluck('state')
            ->all();

        return array_values(array_intersect(array_keys(self::all()), $withCars));
    }
}
