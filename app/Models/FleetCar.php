<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FleetCar extends Model
{
    /**
     * Vehicle-year ranges that define each pricing category. A car's category
     * is always derived from its year, never picked independently, so the
     * two can never drift out of sync.
     */
    public const CATEGORY_YEAR_RANGES = [
        'Regular' => [2005, 2015],
        'Standard' => [2016, 2019],
        'Executive' => [2020, 2026],
    ];

    protected $fillable = [
        'service_type',
        'vehicle_type',
        'year',
        'category',
        'car_name',
        'passengers',
        'features',
        'images',
        'is_active',
    ];

    protected $casts = [
        'year' => 'integer',
        'features' => 'array',
        'images' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (FleetCar $car): void {
            if ($car->year) {
                $car->category = static::categoryForYear($car->year);
            }
        });
    }

    /**
     * Map a vehicle's model year to its pricing category, per
     * CATEGORY_YEAR_RANGES. Returns null for years outside all defined ranges.
     */
    public static function categoryForYear(int $year): ?string
    {
        foreach (static::CATEGORY_YEAR_RANGES as $category => [$from, $to]) {
            if ($year >= $from && $year <= $to) {
                return $category;
            }
        }

        return null;
    }

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
            ->map(fn ($path) => asset('assets/' . $path))
            ->all();
    }
}
