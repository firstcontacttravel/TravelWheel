<?php

namespace App\Services;

use App\Models\ExchangeRate;
use App\Models\VisaFeeComponent;
use App\Models\VisaProcessingOption;
use App\Models\VisaProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The price a customer sees before applying. It mirrors VisaQuotationService:
 * the same fees, the same counting, and the same exchange rates and rounding
 * for the naira checkout total, so the quote at payment matches the search
 * unless a rate or fee changes in between.
 */
class VisaFeeEstimateService
{
    public const CHECKOUT_CURRENCY = 'NGN';

    /** What each fee type is called, and a one-line answer to "what is this for?". */
    private const FEE_TYPES = [
        'government' => ['Government visa fee', 'Set by the destination government and paid on your behalf.'],
        'biometrics' => ['Biometrics', 'Fingerprints and photo taken at the visa centre.'],
        'service' => ['TravelWheel service fee', 'Our fee for preparing, checking and submitting your application.'],
        'processing' => ['Processing fee', 'Charged for the processing speed shown.'],
        'payment' => ['Payment processing', 'Card and bank transfer charges.'],
        'document' => ['Document handling', 'Printing, courier and document handling.'],
        'other' => ['Other fee', null],
    ];

    public function estimate(VisaProduct $product, array $travelers, ?VisaProcessingOption $processingOption = null, array $context = []): array
    {
        $activeOptions = $product->processingOptions->where('is_active', true)->sortBy('sort_order');
        $processingOption ??= $activeOptions->first();

        $fees = $product->fees
            ->where('is_active', true)
            ->filter(fn (VisaFeeComponent $fee): bool => ! $fee->effective_from || $fee->effective_from->lte(now()))
            ->filter(fn (VisaFeeComponent $fee): bool => ! $fee->effective_until || $fee->effective_until->gte(now()))
            ->filter(fn (VisaFeeComponent $fee): bool => (! $fee->processing_option_code && ! $fee->visa_processing_option_id) || ($fee->processing_option_code ? $fee->processing_option_code === $processingOption?->code : $fee->visa_processing_option_id === $processingOption?->id))
            ->filter(fn (VisaFeeComponent $fee): bool => $this->conditionsMatch($fee->conditions ?? [], $context));

        $rates = $this->rates($fees->pluck('currency')->map(fn ($currency) => strtoupper((string) $currency))->unique()->all());

        $lines = $fees->map(function (VisaFeeComponent $fee) use ($travelers, $rates): array {
            $currency = strtoupper((string) $fee->currency);
            $quantity = $fee->calculation_basis === 'per_application'
                ? 1
                : $this->travelerQuantity($fee->traveler_type, $travelers);
            $amount = round((float) $fee->amount * $quantity, 2);
            [$typeLabel, $explanation] = self::FEE_TYPES[$fee->fee_type] ?? self::FEE_TYPES['other'];
            $toAuthority = $fee->payee === 'authority';

            return [
                'name' => $fee->name,
                'fee_type' => $fee->fee_type,
                'type_label' => $typeLabel,
                'explanation' => $toAuthority ? 'Paid by you directly to the embassy or immigration authority, not to TravelWheel.' : $explanation,
                'basis' => $fee->calculation_basis === 'per_application' ? 'per_application' : 'per_traveler',
                'basis_label' => $this->basisLabel($fee, $quantity),
                'currency' => $currency,
                'unit_amount' => (float) $fee->amount,
                'quantity' => $quantity,
                'amount' => $amount,
                'payee' => $fee->payee,
                'pay_online' => (bool) $fee->pay_online,
                'checkout_amount' => isset($rates[$currency]) ? round($amount * $rates[$currency], 2) : null,
            ];
        })->filter(fn (array $line): bool => $line['quantity'] > 0)->values();

        $online = $lines->where('pay_online', true);
        $foreign = $online->pluck('currency')->unique()->reject(fn ($currency) => $currency === self::CHECKOUT_CURRENCY)->values();

        return [
            'processing_option' => $processingOption ? [
                'id' => $processingOption->id,
                'name' => $processingOption->name,
                'minimum_business_days' => $processingOption->minimum_business_days,
                'maximum_business_days' => $processingOption->maximum_business_days,
            ] : null,
            'processing_option_count' => $activeOptions->count(),
            'traveler_count' => array_sum(array_map('intval', $travelers)),
            'lines' => $lines->all(),
            'pay_now_totals' => $this->totals($online),
            'pay_separately_totals' => $this->totals($lines->where('pay_online', false)),
            'checkout' => [
                'currency' => self::CHECKOUT_CURRENCY,
                // Null when a currency has no configured rate: the naira total
                // can only be worked out at payment, so do not guess one here.
                'total' => $online->isNotEmpty() && $online->every(fn (array $line) => $line['checkout_amount'] !== null)
                    ? round($online->sum('checkout_amount'), 2)
                    : null,
                'rates' => $foreign->filter(fn ($currency) => isset($rates[$currency]))->mapWithKeys(fn ($currency) => [$currency => $rates[$currency]])->all(),
            ],
        ];
    }

    /** Same source the checkout quote uses; a missing rate stays missing rather than falling back to 1. */
    private function rates(array $currencies): array
    {
        $configured = ExchangeRate::query()
            ->whereIn('currency', array_diff($currencies, [self::CHECKOUT_CURRENCY]))
            ->pluck('rate', 'currency')
            ->map(fn ($rate) => (float) $rate)
            ->all();

        return in_array(self::CHECKOUT_CURRENCY, $currencies, true)
            ? [self::CHECKOUT_CURRENCY => 1.0] + $configured
            : $configured;
    }

    private function basisLabel(VisaFeeComponent $fee, int $quantity): string
    {
        if ($fee->calculation_basis === 'per_application') {
            return 'Once per application';
        }

        $noun = match ($fee->traveler_type) {
            'adult' => 'adult',
            'child' => 'child',
            'infant' => 'infant',
            default => 'traveler',
        };
        $unit = strtoupper((string) $fee->currency).' '.number_format((float) $fee->amount, 2);

        return "{$unit} × {$quantity} ".($noun === 'child' && $quantity !== 1 ? 'children' : Str::plural($noun, $quantity));
    }

    private function conditionsMatch(array $conditions, array $context): bool
    {
        return collect($conditions)->every(fn (mixed $expected, string $key): bool => data_get($context, $key) == $expected);
    }

    private function travelerQuantity(string $type, array $travelers): int
    {
        return $type === 'all'
            ? array_sum(array_map('intval', $travelers))
            : (int) ($travelers[$type] ?? 0);
    }

    private function totals(Collection $lines): array
    {
        return $lines->groupBy('currency')->map(fn (Collection $currencyLines): float => round($currencyLines->sum('amount'), 2))->all();
    }
}
