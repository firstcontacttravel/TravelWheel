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
    ];

    public static function allKeyed(): array
    {
        return static::all()->keyBy('vehicle_type')->toArray();
    }
}
