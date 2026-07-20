<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FleetCar extends Model
{
    protected $fillable = [
        'service_type',
        'vehicle_type',
        'category',
        'car_name',
        'passengers',
        'features',
        'images',
        'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'images' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForHire($query)
    {
        return $query->where('service_type', 'car_hire');
    }

    public function scopeForTransfer($query)
    {
        return $query->where('service_type', 'transfer');
    }

    public function imageUrls(): array
    {
        return collect($this->images ?? [])
            ->map(fn ($path) => asset('storage/' . $path))
            ->all();
    }
}
