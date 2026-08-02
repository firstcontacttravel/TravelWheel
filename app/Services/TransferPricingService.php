<?php

namespace App\Services;

use App\Models\TransferWearItem;
use App\Models\TransportRate;

class TransferPricingService
{
    public const CATEGORIES = ['Regular', 'Standard', 'Executive'];

    /**
     * Authoritative price breakdown for a Transfer booking:
     *   Base Fare (per vehicle type + category)
     *   + Tear & Wear (sum of the vehicle type's wear-item percentages, off the base fare)
     *   + Fuel (drive-time minutes x the vehicle type's fuel rate per minute)
     *   + Admin Fee (the vehicle type's admin-fee percentage, off the subtotal)
     *   = Total
     */
    public function quote(string $vehicleType, string $category, int $durationMins): array
    {
        $rate = TransportRate::where('vehicle_type', $vehicleType)->first();

        $baseFare = (int) match ($category) {
            'Regular' => $rate->transfer_base_regular ?? 0,
            'Standard' => $rate->transfer_base_standard ?? 0,
            'Executive' => $rate->transfer_base_executive ?? 0,
            default => 0,
        };

        return $this->quoteForBaseFare($vehicleType, $baseFare, $durationMins);
    }

    /**
     * Same Base Fare + Tear & Wear + Fuel + Admin Fee formula as quote(),
     * but for a caller-supplied base fare — used by Car Hire, which prices
     * its own categories but shares the Transfer wear/fuel/admin-fee rates.
     */
    public function quoteForBaseFare(string $vehicleType, int $baseFare, int $durationMins): array
    {
        $rate = TransportRate::where('vehicle_type', $vehicleType)->first();

        $wearPercent = TransferWearItem::totalPercentageFor($vehicleType);
        $tearWear = round($baseFare * $wearPercent / 100, 2);

        $fuelRatePerMinute = (int) ($rate->transfer_fuel_rate_per_minute ?? 0);
        $fuel = round($durationMins * $fuelRatePerMinute, 2);

        $subtotal = $baseFare + $tearWear + $fuel;

        $adminFeePercent = (float) ($rate->transfer_admin_fee_percent ?? 0);
        $adminFee = round($subtotal * $adminFeePercent / 100, 2);

        $total = round($subtotal + $adminFee, 2);

        return [
            'base_fare' => $baseFare,
            'wear_percent' => $wearPercent,
            'tear_wear_amount' => $tearWear,
            'fuel_rate_per_minute' => $fuelRatePerMinute,
            'duration_mins' => $durationMins,
            'fuel_amount' => $fuel,
            'admin_fee_percent' => $adminFeePercent,
            'admin_fee_amount' => $adminFee,
            'total' => $total,
        ];
    }

    /**
     * Wear items (name + percentage) for a vehicle type — used to show the
     * Tear & Wear breakdown to the customer.
     */
    public function wearItemsFor(string $vehicleType): array
    {
        return TransferWearItem::forVehicleType($vehicleType)
            ->get(['name', 'percentage'])
            ->map(fn ($item) => ['name' => $item->name, 'percentage' => (float) $item->percentage])
            ->all();
    }

    /**
     * Rates + wear data per vehicle type, for rendering the public booking
     * page and computing a live quote client-side before server verification.
     */
    public function ratesData(): array
    {
        $rates = TransportRate::get()->keyBy('vehicle_type');
        $data = [];

        foreach ($rates as $vehicleType => $rate) {
            $data[$vehicleType] = [
                'transfer_base_fares' => [
                    'Regular' => (int) $rate->transfer_base_regular,
                    'Standard' => (int) $rate->transfer_base_standard,
                    'Executive' => (int) $rate->transfer_base_executive,
                ],
                'car_hire_base_fares' => [
                    'Regular' => (int) $rate->carhire_base_regular,
                    'Standard' => (int) $rate->carhire_base_standard,
                    'Executive' => (int) $rate->carhire_base_executive,
                ],
                'fuel_rate_per_minute' => (int) $rate->transfer_fuel_rate_per_minute,
                'admin_fee_percent' => (float) $rate->transfer_admin_fee_percent,
                'wear_items' => $this->wearItemsFor($vehicleType),
                'wear_percent' => TransferWearItem::totalPercentageFor($vehicleType),
            ];
        }

        return $data;
    }
}
