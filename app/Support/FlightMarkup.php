<?php

namespace App\Support;

use App\Models\ExchangeRate;
use App\Models\FlightServiceCharge;
use Throwable;

class FlightMarkup
{
    private const NIGERIA = 'nigeria';

    private const SUPPLIER_CURRENCY = 'USD';

    private static ?float $cachedUsdRate = null;

    private const DEFAULT_CHARGES = [
        'domestic' => [
            'economy' => 20000.0,
            'premium_economy' => 30000.0,
            'business' => 40000.0,
            'first' => 50000.0,
        ],
        'from_nigeria' => [
            'economy' => 30000.0,
            'premium_economy' => 60000.0,
            'business' => 100000.0,
            'first' => 150000.0,
        ],
        'touches_nigeria' => [
            'economy' => 70000.0,
            'premium_economy' => 120000.0,
            'business' => 200000.0,
            'first' => 300000.0,
        ],
        'not_nigeria' => [
            'economy' => 100000.0,
            'premium_economy' => 170000.0,
            'business' => 240000.0,
            'first' => 350000.0,
        ],
    ];

    private static ?array $cachedCharges = null;

    public static function apply(array $flight): array
    {
        $rate = self::usdToNgnRate();

        // The airline API is queried with requiredCurrency=USD, but the markup
        // table below is denominated in Naira — convert the supplier fare to NGN
        // before adding the markup so both operands are in the same currency.
        $supplierPrice = self::convert((float) ($flight['supplierPrice'] ?? $flight['price'] ?? 0), $rate);
        $category = self::routeCategory($flight);
        $cabin = self::cabinCategory($flight);
        $passengerCount = self::passengerCount($flight);
        $chargePerPassenger = self::chargeFor($category, $cabin);
        $markup = $chargePerPassenger * $passengerCount;

        $flight['supplierPrice'] = $supplierPrice;
        $flight['markupAmount'] = round($markup, 2);
        $flight['markupRatePerPassenger'] = round($chargePerPassenger, 2);
        $flight['markupPassengerCount'] = $passengerCount;
        $flight['markupCategory'] = $category;
        $flight['markupCabin'] = $cabin;
        $flight['price'] = round($supplierPrice + $markup, 2);
        $flight['baseFare'] = self::convert((float) ($flight['baseFare'] ?? 0), $rate);
        $flight['totalTax'] = self::convert((float) ($flight['totalTax'] ?? 0), $rate);
        $flight['currency'] = 'NGN';

        if (! empty($flight['fareBreakdown']) && is_array($flight['fareBreakdown'])) {
            $flight['fareBreakdown'] = array_map(function ($fb) use ($rate) {
                foreach (['baseFare', 'totalFare', 'serviceTax', 'surcharges', 'changePenalty', 'refundPenalty'] as $field) {
                    if (isset($fb[$field])) {
                        $fb[$field] = self::convert((float) $fb[$field], $rate);
                    }
                }
                $fb['currency'] = 'NGN';

                return $fb;
            }, $flight['fareBreakdown']);
        }

        return $flight;
    }

    public static function forgetCachedConfiguration(): void
    {
        self::$cachedUsdRate = null;
        self::$cachedCharges = null;
    }

    private static function convert(float $amount, float $rate): float
    {
        return round($amount * $rate, 2);
    }

    private static function usdToNgnRate(): float
    {
        return self::$cachedUsdRate ??= ExchangeRate::rateFor(self::SUPPLIER_CURRENCY);
    }

    private static function chargeFor(string $category, string $cabin): float
    {
        if (self::$cachedCharges === null) {
            try {
                self::$cachedCharges = FlightServiceCharge::allKeyed();
            } catch (Throwable) {
                self::$cachedCharges = [];
            }
        }

        return (float) (self::$cachedCharges[$category.'.'.$cabin] ?? self::DEFAULT_CHARGES[$category][$cabin] ?? 0);
    }

    private static function passengerCount(array $flight): int
    {
        $quantity = collect($flight['fareBreakdown'] ?? [])
            ->sum(fn ($breakdown): int => max(0, (int) ($breakdown['qty'] ?? 0)));

        return max(1, $quantity);
    }

    public static function routeCategory(array $flight): string
    {
        $legs = self::segments($flight);
        $first = $legs[0] ?? [];

        if ($legs !== [] && self::allSegmentsWithinNigeria($legs)) {
            return 'domestic';
        }

        $originNigeria = self::isNigeria($first['fromCountry'] ?? null);
        $firstDestinationNigeria = self::isNigeria($first['toCountry'] ?? null);

        if ($originNigeria && ! $firstDestinationNigeria) {
            return 'from_nigeria';
        }

        foreach ($legs as $segment) {
            if (self::isNigeria($segment['fromCountry'] ?? null) || self::isNigeria($segment['toCountry'] ?? null)) {
                return 'touches_nigeria';
            }
        }

        return 'not_nigeria';
    }

    private static function allSegmentsWithinNigeria(array $legs): bool
    {
        foreach ($legs as $segment) {
            if (! self::isNigeria($segment['fromCountry'] ?? null) || ! self::isNigeria($segment['toCountry'] ?? null)) {
                return false;
            }
        }

        return true;
    }

    public static function cabinCategory(array $flight): string
    {
        $value = strtolower(trim((string) (
            $flight['cabinCode']
            ?? data_get($flight, 'segments.0.cabinCode')
            ?? data_get($flight, 'multiLegs.0.segments.0.cabinCode')
            ?? $flight['cabin']
            ?? data_get($flight, 'segments.0.cabin')
            ?? data_get($flight, 'multiLegs.0.segments.0.cabin')
            ?? 'Y'
        )));

        return match ($value) {
            's', 'w', 'premiumeconomy', 'premium economy', 'premium_economy' => 'premium_economy',
            'c', 'j', 'biz', 'business' => 'business',
            'f', 'p', 'first', 'firstclass', 'first class' => 'first',
            default => 'economy',
        };
    }

    private static function segments(array $flight): array
    {
        $segments = [];

        foreach (($flight['segments'] ?? []) as $segment) {
            if (is_array($segment)) {
                $segments[] = $segment;
            }
        }

        foreach (($flight['returnSegments'] ?? []) as $segment) {
            if (is_array($segment)) {
                $segments[] = $segment;
            }
        }

        foreach (($flight['multiLegs'] ?? []) as $leg) {
            foreach (($leg['segments'] ?? []) as $segment) {
                if (is_array($segment)) {
                    $segments[] = $segment;
                }
            }
        }

        return $segments;
    }

    private static function isNigeria(mixed $country): bool
    {
        return strtolower(trim((string) $country)) === self::NIGERIA;
    }
}
