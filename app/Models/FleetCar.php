<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FleetCar extends Model
{
    /**
     * Vehicle-year ranges that define each pricing category. A car's category
     * is always derived from its year, never picked independently, so the
     * two can never drift out of sync. A null upper bound means "and above".
     */
    public const CATEGORY_YEAR_RANGES = [
        'Regular' => [2005, 2015],
        'Standard' => [2016, 2019],
        'Executive' => [2020, null],
    ];

    /**
     * Per-vehicle-type overrides of CATEGORY_YEAR_RANGES. Luxury cars are
     * newer saloons or SUVs, so their categories start later.
     */
    public const VEHICLE_TYPE_YEAR_RANGES = [
        'luxury' => [
            'Regular' => [2016, 2020],
            'Standard' => [2021, 2023],
            'Executive' => [2024, null],
        ],
    ];

    /** What the year range applies to, per vehicle type ("Cars" by default). */
    public const VEHICLE_TYPE_YEAR_SUBJECT = [
        'luxury' => 'Saloon or SUV',
    ];

    public static function yearRangesFor(?string $vehicleType): array
    {
        return static::VEHICLE_TYPE_YEAR_RANGES[$vehicleType] ?? static::CATEGORY_YEAR_RANGES;
    }

    /**
     * The bracketed note after a category on the booking page, e.g.
     * "Cars within year 2005 to 2015" or "Saloon or SUV within year 2016 to 2020".
     */
    public static function categoryYearNote(string $category, ?string $vehicleType = null): ?string
    {
        $range = static::yearRangeLabel($category, $vehicleType);

        return $range === null
            ? null
            : (static::VEHICLE_TYPE_YEAR_SUBJECT[$vehicleType] ?? 'Cars').' within year '.$range;
    }

    /**
     * Customer-facing year range for a category, e.g. "2005 to 2015" or
     * "2020 and above" — shown on the booking page and in admin.
     */
    public static function yearRangeLabel(string $category, ?string $vehicleType = null): ?string
    {
        [$from, $to] = static::yearRangesFor($vehicleType)[$category] ?? [null, null];

        if ($from === null) {
            return null;
        }

        return $to === null ? "{$from} and above" : "{$from} to {$to}";
    }

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
                $car->category = static::categoryForYear($car->year, $car->vehicle_type);
            }
        });
    }

    /**
     * Map a vehicle's model year to its pricing category, per
     * its vehicle type's year ranges. Returns null for years outside all of them.
     */
    public static function categoryForYear(int $year, ?string $vehicleType = null): ?string
    {
        foreach (static::yearRangesFor($vehicleType) as $category => [$from, $to]) {
            if ($year >= $from && ($to === null || $year <= $to)) {
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
