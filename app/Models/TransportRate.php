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
        'transfer_admin_fee_percent',
        'carhire_base_regular',
        'carhire_base_standard',
        'carhire_base_executive',
    ];

    public static function allKeyed(): array
    {
        return static::all()->keyBy('vehicle_type')->toArray();
    }
}
