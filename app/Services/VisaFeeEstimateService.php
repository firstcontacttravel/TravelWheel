<?php

namespace App\Services;

use App\Models\VisaFeeComponent;
use App\Models\VisaProcessingOption;
use App\Models\VisaProduct;
use Illuminate\Support\Collection;

class VisaFeeEstimateService
{
    public function estimate(VisaProduct $product, array $travelers, ?VisaProcessingOption $processingOption = null, array $context = []): array
    {
        $processingOption ??= $product->processingOptions->where('is_active', true)->sortBy('sort_order')->first();

        $fees = $product->fees
            ->where('is_active', true)
            ->filter(fn (VisaFeeComponent $fee): bool => ! $fee->effective_from || $fee->effective_from->lte(now()))
            ->filter(fn (VisaFeeComponent $fee): bool => ! $fee->effective_until || $fee->effective_until->gte(now()))
            ->filter(fn (VisaFeeComponent $fee): bool => (! $fee->processing_option_code && ! $fee->visa_processing_option_id) || ($fee->processing_option_code ? $fee->processing_option_code === $processingOption?->code : $fee->visa_processing_option_id === $processingOption?->id))
            ->filter(fn (VisaFeeComponent $fee): bool => $this->conditionsMatch($fee->conditions ?? [], $context));

        $lines = $fees->map(function (VisaFeeComponent $fee) use ($travelers): array {
            $quantity = $fee->calculation_basis === 'per_application'
                ? 1
                : $this->travelerQuantity($fee->traveler_type, $travelers);

            return [
                'name' => $fee->name,
                'currency' => $fee->currency,
                'unit_amount' => (float) $fee->amount,
                'quantity' => $quantity,
                'amount' => round((float) $fee->amount * $quantity, 2),
                'payee' => $fee->payee,
                'pay_online' => (bool) $fee->pay_online,
            ];
        })->filter(fn (array $line): bool => $line['quantity'] > 0)->values();

        return [
            'processing_option' => $processingOption ? [
                'id' => $processingOption->id,
                'name' => $processingOption->name,
                'minimum_business_days' => $processingOption->minimum_business_days,
                'maximum_business_days' => $processingOption->maximum_business_days,
            ] : null,
            'lines' => $lines->all(),
            'pay_now_totals' => $this->totals($lines->where('pay_online', true)),
            'pay_separately_totals' => $this->totals($lines->where('pay_online', false)),
        ];
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
