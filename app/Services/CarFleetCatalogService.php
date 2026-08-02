<?php

namespace App\Services;

use App\Models\FleetCar;

class CarFleetCatalogService
{
    public const VEHICLE_TYPES = ['saloon', 'suv', 'van', 'bus', 'luxury'];

    public function __construct(private readonly TransferPricingService $transferPricing = new TransferPricingService())
    {
    }

    /**
     * Car-hire categories (Regular/Standard/Executive) per vehicle type, with
     * priced fleet models — used to render the public booking page.
     */
    public function categories(): array
    {
        $ratesData = $this->transferPricing->ratesData();
        $fleetCars = FleetCar::active()->forHire()->get()->groupBy('vehicle_type');

        $categories = [];

        foreach (self::VEHICLE_TYPES as $vtype) {
            $rateInfo = $ratesData[$vtype] ?? null;
            $priceMap = $rateInfo['car_hire_base_fares'] ?? ['Regular' => 0, 'Standard' => 0, 'Executive' => 0];

            $carsForType = $fleetCars->get($vtype, collect());
            $items = [];

            foreach (['Regular', 'Standard', 'Executive'] as $catName) {
                $modelsInCat = $carsForType->where('category', $catName);
                $passengers = $modelsInCat->first()->passengers ?? $this->defaultPassengerLabel($vtype);

                $models = $modelsInCat->map(function ($car) use ($vtype) {
                    $hasImages = $car->images && count($car->images) > 0;

                    return [
                        'name' => $car->car_name,
                        'image' => $hasImages ? asset('storage/' . $car->images[0]) : $this->defaultVehicleImage($vtype),
                        'images' => $hasImages
                            ? collect($car->images)->map(fn ($img) => asset('storage/' . $img))->all()
                            : [$this->defaultVehicleImage($vtype)],
                        'features' => $car->features ?? [],
                    ];
                })->values()->all();

                $catImages = $modelsInCat->first() && $modelsInCat->first()->images
                    ? collect($modelsInCat->first()->images)->map(fn ($img) => asset('storage/' . $img))->all()
                    : [$this->defaultVehicleImage($vtype)];

                $items[] = [
                    'name' => $catName,
                    'price' => (int) $priceMap[$catName],
                    'passengers' => $passengers,
                    'images' => $catImages,
                    'models' => $models,
                ];
            }

            $categories[$vtype] = [
                'fuel_rate_per_minute' => (int) ($rateInfo['fuel_rate_per_minute'] ?? 0),
                'admin_fee_percent' => (float) ($rateInfo['admin_fee_percent'] ?? 0),
                'wear_items' => $rateInfo['wear_items'] ?? [],
                'wear_percent' => (float) ($rateInfo['wear_percent'] ?? 0),
                'items' => $items,
            ];
        }

        return $categories;
    }

    /**
     * Transfer vehicles per vehicle type, grouped by category
     * (Regular/Standard/Executive) with the rate data needed to compute the
     * Base Fare + Tear & Wear + Fuel + Admin Fee formula — used to render
     * the public booking page.
     */
    public function transferVehicles(): array
    {
        $ratesData = $this->transferPricing->ratesData();
        $fleetCars = FleetCar::active()->forTransfer()->get()->groupBy('vehicle_type');

        $transferVehicles = [];

        foreach (self::VEHICLE_TYPES as $vtype) {
            $rateInfo = $ratesData[$vtype] ?? null;
            $carsForType = $fleetCars->get($vtype, collect());

            $items = [];
            foreach (['Regular', 'Standard', 'Executive'] as $catName) {
                $modelsInCat = $carsForType->where('category', $catName);
                $passengers = $modelsInCat->first()->passengers ?? $this->defaultPassengerLabel($vtype);

                $models = $modelsInCat->map(function ($car) use ($vtype) {
                    $hasImages = $car->images && count($car->images) > 0;

                    return [
                        'name' => $car->car_name,
                        'image' => $hasImages ? asset('storage/' . $car->images[0]) : $this->defaultVehicleImage($vtype),
                        'images' => $hasImages
                            ? collect($car->images)->map(fn ($img) => asset('storage/' . $img))->all()
                            : [$this->defaultVehicleImage($vtype)],
                        'features' => $car->features ?? [],
                    ];
                })->values()->all();

                $catImages = $modelsInCat->first() && $modelsInCat->first()->images
                    ? collect($modelsInCat->first()->images)->map(fn ($img) => asset('storage/' . $img))->all()
                    : [$this->defaultVehicleImage($vtype)];

                $items[] = [
                    'name' => $catName,
                    'price' => (int) ($rateInfo['transfer_base_fares'][$catName] ?? 0),
                    'passengers' => $passengers,
                    'images' => $catImages,
                    'models' => $models,
                ];
            }

            $transferVehicles[$vtype] = [
                'fuel_rate_per_minute' => (int) ($rateInfo['fuel_rate_per_minute'] ?? 0),
                'admin_fee_percent' => (float) ($rateInfo['admin_fee_percent'] ?? 0),
                'wear_items' => $rateInfo['wear_items'] ?? [],
                'wear_percent' => (float) ($rateInfo['wear_percent'] ?? 0),
                'items' => $items,
            ];
        }

        return $transferVehicles;
    }

    public function typeThumbs(array $categories, array $transferVehicles): array
    {
        $thumbs = [];

        foreach (self::VEHICLE_TYPES as $vtype) {
            $thumbs[$vtype] = $categories[$vtype]['items'][0]['images'][0]
                ?? $transferVehicles[$vtype]['items'][0]['images'][0]
                ?? $this->defaultVehicleImage($vtype);
        }

        return $thumbs;
    }

    /**
     * Server-side price lookups (no images) — used to verify submitted prices.
     */
    public function categoriesData(): array
    {
        $ratesData = $this->transferPricing->ratesData();
        $data = [];

        foreach (self::VEHICLE_TYPES as $vtype) {
            $baseFares = $ratesData[$vtype]['car_hire_base_fares'] ?? ['Regular' => 0, 'Standard' => 0, 'Executive' => 0];
            $data[$vtype] = [
                'items' => [
                    ['name' => 'Regular', 'price' => (int) ($baseFares['Regular'] ?? 0)],
                    ['name' => 'Standard', 'price' => (int) ($baseFares['Standard'] ?? 0)],
                    ['name' => 'Executive', 'price' => (int) ($baseFares['Executive'] ?? 0)],
                ],
            ];
        }

        return $data;
    }

    /**
     * Whether an active Transfer fleet car exists for the given vehicle
     * type + category + name — used to validate a Transfer submission
     * before computing its (server-authoritative) price.
     */
    public function transferVehicleExists(string $vehicleType, string $category, string $carName): bool
    {
        return FleetCar::active()->forTransfer()
            ->where('vehicle_type', $vehicleType)
            ->where('category', $category)
            ->where('car_name', $carName)
            ->exists();
    }

    public function defaultPassengerLabel(string $vtype): string
    {
        return match ($vtype) {
            'van' => 'Up to 5 Passengers',
            'bus' => 'Up to 12 Passengers',
            'luxury' => '1 – 4 Passengers',
            default => '1 – 3 Passengers',
        };
    }

    public function defaultVehicleImage(string $vtype): string
    {
        $colors = [
            'saloon' => '#0d1883',
            'suv' => '#1a5d8f',
            'van' => '#1a7a4e',
            'bus' => '#b07000',
            'luxury' => '#7a1f8f',
        ];
        $color = $colors[$vtype] ?? '#0d1883';

        $svg = match ($vtype) {
            'saloon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 100"><rect width="200" height="100" fill="#eef1ff"/><path d="M30 65 L40 40 Q45 32 60 32 L140 32 Q155 32 160 40 L170 65 L170 75 L30 75 Z" fill="' . $color . '" opacity="0.85"/><circle cx="55" cy="75" r="11" fill="#1a1a1a"/><circle cx="145" cy="75" r="11" fill="#1a1a1a"/><rect x="55" y="38" width="90" height="22" rx="4" fill="#cdd6ff" opacity="0.7"/></svg>',
            'suv' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 100"><rect width="200" height="100" fill="#eaf2f8"/><path d="M28 62 L35 35 Q40 28 55 28 L145 28 Q160 28 165 35 L172 62 L172 76 L28 76 Z" fill="' . $color . '" opacity="0.85"/><circle cx="55" cy="76" r="12" fill="#1a1a1a"/><circle cx="145" cy="76" r="12" fill="#1a1a1a"/><rect x="48" y="34" width="104" height="24" rx="4" fill="#c8e0ee" opacity="0.7"/></svg>',
            'van' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 100"><rect width="200" height="100" fill="#e8f8f0"/><path d="M25 30 Q25 22 35 22 L155 22 Q170 22 170 35 L170 70 L25 70 Z" fill="' . $color . '" opacity="0.85"/><circle cx="55" cy="74" r="11" fill="#1a1a1a"/><circle cx="145" cy="74" r="11" fill="#1a1a1a"/><rect x="32" y="28" width="40" height="26" rx="3" fill="#bfeed4" opacity="0.7"/></svg>',
            'bus' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 100"><rect width="200" height="100" fill="#fff6e3"/><rect x="20" y="20" width="160" height="48" rx="6" fill="' . $color . '" opacity="0.85"/><circle cx="50" cy="72" r="11" fill="#1a1a1a"/><circle cx="150" cy="72" r="11" fill="#1a1a1a"/><rect x="28" y="28" width="28" height="18" rx="2" fill="#fde6b0" opacity="0.8"/><rect x="60" y="28" width="28" height="18" rx="2" fill="#fde6b0" opacity="0.8"/><rect x="92" y="28" width="28" height="18" rx="2" fill="#fde6b0" opacity="0.8"/><rect x="124" y="28" width="28" height="18" rx="2" fill="#fde6b0" opacity="0.8"/></svg>',
            'luxury' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 100"><rect width="200" height="100" fill="#f5eaf8"/><path d="M22 64 L34 38 Q40 30 58 30 L150 30 Q165 32 172 42 L178 64 L178 76 L22 76 Z" fill="' . $color . '" opacity="0.85"/><circle cx="55" cy="76" r="11" fill="#1a1a1a"/><circle cx="148" cy="76" r="11" fill="#1a1a1a"/><rect x="58" y="36" width="100" height="22" rx="4" fill="#e3c8ee" opacity="0.7"/></svg>',
            default => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 100"><rect width="200" height="100" fill="#eef1ff"/><circle cx="100" cy="50" r="30" fill="' . $color . '" opacity="0.6"/></svg>',
        };

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
