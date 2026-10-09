<?php

namespace App\Support;

use App\Models\AppSetting;

/**
 * The vehicles a Protocol customer can pick for a pick-up or drop-off
 * optional request. Prices are shown to the customer for information only:
 * they are not added to the protocol amount, and are settled separately.
 * Admin sets them from the Protocols list page ("Pick-up vehicle prices").
 */
class ProtocolVehicles
{
    public const VEHICLES = [
        'saloon' => ['name' => 'Saloon Comfort', 'seats' => 3, 'image' => 'assets/image/saloon.jpg'],
        'suv' => ['name' => 'SUV Business', 'seats' => 3, 'image' => 'assets/image/suv.jpg'],
        'minivan' => ['name' => 'Mini Van', 'seats' => 5, 'image' => 'assets/image/minivan.png'],
    ];

    /** @return array<string, array{name: string, seats: int, image: string, price: ?float}> */
    public static function all(): array
    {
        return collect(self::VEHICLES)
            ->map(fn (array $vehicle, string $key) => $vehicle + ['price' => self::price($key)])
            ->all();
    }

    public static function find(?string $key): ?array
    {
        return $key !== null && isset(self::VEHICLES[$key]) ? self::all()[$key] : null;
    }

    /** Null until admin sets a price; the customer then sees "Price on request". */
    public static function price(string $key): ?float
    {
        $value = AppSetting::get(self::priceKey($key));

        return $value === null || $value === '' ? null : (float) $value;
    }

    public static function setPrice(string $key, mixed $price): void
    {
        AppSetting::set(self::priceKey($key), $price === null || $price === '' ? null : (float) $price);
    }

    public static function priceLabel(?float $price): string
    {
        return $price === null ? 'Price on request' : '₦'.number_format($price);
    }

    /** "Pick-up", "Drop-off" and their "& Escort" versions need a vehicle; "Police Escort" alone doesn't. */
    public static function needsVehicle(?string $request): bool
    {
        return $request !== null && (str_contains($request, 'Pick-up') || str_contains($request, 'Drop-off'));
    }

    private static function priceKey(string $key): string
    {
        return 'protocol_vehicle_price_'.$key;
    }
}
