<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportRate extends Model
{
    protected $fillable = [
        'vehicle_type',
        'price_regular',
        'price_standard',
        'price_executive',
        'fuel_rate_per_km',
        'hourly_rate',
        'transfer_rate_per_km',
        'transfer_base_regular',
        'transfer_base_standard',
        'transfer_base_executive',
        'transfer_fuel_rate_per_minute',
        'fuel_pump_price',
        'fuel_litres_per_hour',
        'transfer_admin_fee_percent',
        'carhire_base_regular',
        'carhire_base_standard',
        'carhire_base_executive',
    ];

    /**
     * The fuel rate per minute is derived, not entered: it follows the pump
     * price and the vehicle type's consumption, so raising the pump price
     * raises every vehicle's fuel/min (and so every Car Hire and Transfer
     * quote).
     */
    protected static function booted(): void
    {
        static::saving(function (TransportRate $rate): void {
            $rate->transfer_fuel_rate_per_minute = static::fuelRatePerMinuteFor(
                (int) $rate->fuel_pump_price,
                (float) $rate->fuel_litres_per_hour,
            );
        });
    }

    public static function fuelRatePerMinuteFor(int $pumpPrice, float $litresPerHour): int
    {
        return (int) round($pumpPrice * $litresPerHour / 60);
    }

    public static function allKeyed(): array
    {
        return static::all()->keyBy('vehicle_type')->toArray();
    }
}
