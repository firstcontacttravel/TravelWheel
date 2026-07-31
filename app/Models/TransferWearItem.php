<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferWearItem extends Model
{
    protected $fillable = [
        'vehicle_type',
        'name',
        'percentage',
        'sort_order',
    ];

    protected $casts = [
        'percentage' => 'float',
        'sort_order' => 'integer',
    ];

    public function scopeForVehicleType($query, string $vehicleType)
    {
        return $query->where('vehicle_type', $vehicleType)->orderBy('sort_order');
    }

    public static function totalPercentageFor(string $vehicleType): float
    {
        return (float) static::where('vehicle_type', $vehicleType)->sum('percentage');
    }
}
