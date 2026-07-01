<?php

namespace App\Services;

use App\Models\VisaApplication;
use App\Models\VisaExchangeRate;
use App\Models\VisaQuote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VisaQuotationService
{
    public function create(VisaApplication $application): VisaQuote
    {
        if ($application->completed_step < 7 || ! $application->declaration_accepted) {
            throw ValidationException::withMessages(['quote' => 'Complete the application and accept the declaration before requesting a quote.']);
        }

        $application->load(['product.fees', 'product.processingOptions', 'product.optionalServices', 'travelers', 'serviceSelections']);
        $product = $application->product;
        abort_unless($product && $product->newQuery()->whereKey($product->id)->currentlyPublished()->exists(), 422, 'This visa product is no longer available.');

        $counts = $this->travelerCounts($application);
        $context = ['nationality_country_id' => $application->nationality_country_id];
        $fees = $product->fees->where('is_active', true)
            ->filter(fn ($fee) => ! $fee->effective_from || $fee->effective_from->lte(now()))
            ->filter(fn ($fee) => ! $fee->effective_until || $fee->effective_until->gte(now()))
            ->filter(fn ($fee) => (! $fee->processing_option_code && ! $fee->visa_processing_option_id) || ($fee->processing_option_code ? $fee->processing_option_code === $application->processingOption?->code : $fee->visa_processing_option_id === $application->visa_processing_option_id))
            ->filter(fn ($fee) => $this->conditionsMatch($fee->conditions ?? [], $context));

        $rawItems = [];
        foreach ($fees as $fee) {
            $quantity = $fee->calculation_basis === 'per_application' ? 1 : ($counts[$fee->traveler_type] ?? 0);
            if ($quantity < 1) {
                continue;
            }
            $rawItems[] = ['fee_id' => $fee->id, 'service_id' => null, 'name' => $fee->name, 'type' => $fee->fee_type, 'traveler_type' => $fee->traveler_type, 'basis' => $fee->calculation_basis, 'quantity' => $quantity, 'currency' => strtoupper($fee->currency), 'unit' => (float) $fee->amount, 'payee' => $fee->payee, 'online' => (bool) $fee->pay_online, 'sort' => $fee->sort_order];
        }

        $selectedIds = $application->serviceSelections->where('selected', true)->pluck('visa_optional_service_id');
        foreach ($product->optionalServices->where('is_active', true)->whereIn('id', $selectedIds) as $service) {
            if ($service->amount === null || ! $service->currency) {
                continue;
            }
            $quantity = $service->pricing_model === 'per_traveler' ? $application->travelers->count() : 1;
            $rawItems[] = ['fee_id' => null, 'service_id' => $service->id, 'name' => $service->name, 'type' => 'optional_service', 'traveler_type' => $service->pricing_model === 'per_traveler' ? 'all' : null, 'basis' => $service->pricing_model === 'per_traveler' ? 'per_traveler' : 'per_application', 'quantity' => $quantity, 'currency' => strtoupper($service->currency), 'unit' => (float) $service->amount, 'payee' => 'travelwheel', 'online' => true, 'sort' => 1000 + $service->sort_order];
        }

        if ($rawItems === []) {
            throw ValidationException::withMessages(['quote' => 'This product has no active price components.']);
        }
        $rates = $this->ratesFor(collect($rawItems)->pluck('currency')->unique()->all(), 'NGN');
        $fingerprint = hash('sha256', json_encode([$product->id, $product->version, $application->visa_processing_option_id, $counts, $rawItems, $rates], JSON_THROW_ON_ERROR));

        $existing = $application->quotes()->where('status', 'active')->where('pricing_fingerprint', $fingerprint)->where('expires_at', '>', now())->latest()->first();
        if ($existing) {
            return $existing->load('items');
        }

        return DB::transaction(function () use ($application, $product, $rawItems, $rates, $fingerprint): VisaQuote {
            $application->quotes()->where('status', 'active')->update(['status' => 'superseded', 'superseded_at' => now()]);
            $quote = $application->quotes()->create([
                'reference' => (string) Str::ulid(), 'visa_product_id' => $product->id,
                'visa_processing_option_id' => $application->visa_processing_option_id, 'product_version' => $product->version,
                'status' => 'active', 'checkout_currency' => 'NGN', 'payable_total' => 0,
                'source_totals' => [], 'exchange_rate_snapshot' => $rates, 'pricing_fingerprint' => $fingerprint,
                'expires_at' => now()->addMinutes((int) config('services.visa.quote_ttl_minutes', 30)),
            ]);
            $sourceTotals = [];
            $payable = 0;
            foreach ($rawItems as $index => $item) {
                $sourceTotal = round($item['unit'] * $item['quantity'], 2);
                $checkoutUnit = round($item['unit'] * $rates[$item['currency']]['rate'], 2);
                $checkoutTotal = round($sourceTotal * $rates[$item['currency']]['rate'], 2);
                $sourceTotals[$item['currency']] = round(($sourceTotals[$item['currency']] ?? 0) + $sourceTotal, 2);
                if ($item['online']) {
                    $payable = round($payable + $checkoutTotal, 2);
                }
                $quote->items()->create([
                    'visa_fee_component_id' => $item['fee_id'], 'visa_optional_service_id' => $item['service_id'],
                    'name' => $item['name'], 'item_type' => $item['type'], 'traveler_type' => $item['traveler_type'],
                    'calculation_basis' => $item['basis'], 'quantity' => $item['quantity'], 'source_currency' => $item['currency'],
                    'source_unit_amount' => $item['unit'], 'source_total' => $sourceTotal, 'exchange_rate' => $rates[$item['currency']]['rate'],
                    'checkout_currency' => 'NGN', 'checkout_unit_amount' => $checkoutUnit, 'checkout_total' => $checkoutTotal,
                    'payee' => $item['payee'], 'pay_online' => $item['online'], 'sort_order' => $item['sort'] ?: $index,
                ]);
            }
            if ($payable <= 0) {
                throw ValidationException::withMessages(['quote' => 'The online payable total must be greater than zero.']);
            }
            $quote->update(['payable_total' => $payable, 'source_totals' => $sourceTotals]);
            if ($application->status === 'draft') {
                $application->update(['status' => 'awaiting_payment']);
                $application->statusHistory()->create(['from_status' => 'draft', 'to_status' => 'awaiting_payment', 'actor_type' => 'applicant', 'reason' => 'Immutable quote created', 'metadata' => ['quote_reference' => $quote->reference]]);
            }

            return $quote->fresh('items');
        });
    }

    private function ratesFor(array $currencies, string $target): array
    {
        $rates = [];
        foreach ($currencies as $currency) {
            if ($currency === $target) {
                $rates[$currency] = ['rate' => 1, 'source' => 'identity', 'captured_at' => now()->toIso8601String()];

                continue;
            }
            $rate = VisaExchangeRate::query()->where('source_currency', $currency)->where('target_currency', $target)->where('is_active', true)->where('effective_from', '<=', now())->where(fn ($q) => $q->whereNull('effective_until')->orWhere('effective_until', '>=', now()))->latest('effective_from')->first();
            if (! $rate) {
                throw ValidationException::withMessages(['quote' => "No active {$currency}/{$target} exchange rate is configured."]);
            }
            $rates[$currency] = ['rate' => (float) $rate->rate, 'source' => $rate->source, 'rate_id' => $rate->id, 'captured_at' => now()->toIso8601String()];
        }

        return $rates;
    }

    private function travelerCounts(VisaApplication $application): array
    {
        $counts = ['all' => $application->travelers->count(), 'adult' => 0, 'child' => 0, 'infant' => 0, 'individual' => 0, 'company' => 0, 'minor_nigerian' => 0, 'minor_foreign' => 0];
        foreach ($application->travelers as $traveler) {
            $counts[$traveler->traveler_type]++;
            if ($traveler->applicant_type) {
                $counts[$traveler->applicant_type]++;
            }
        }

        return $counts;
    }

    private function conditionsMatch(array $conditions, array $context): bool
    {
        return collect($conditions)->every(fn ($expected, $key) => data_get($context, $key) == $expected);
    }
}
