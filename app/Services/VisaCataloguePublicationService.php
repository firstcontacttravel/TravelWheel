<?php

namespace App\Services;

use App\Enums\VisaEligibilityMode;
use App\Enums\VisaPublicationStatus;
use App\Models\VisaProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VisaCataloguePublicationService
{
    public function errors(VisaProduct $product): array
    {
        $product->loadMissing([
            'destinationCountry',
            'eligibilityRules',
            'processingOptions',
            'fees.processingOption',
            'requirements',
        ]);

        $errors = [];

        if (! $product->destinationCountry?->is_active) {
            $errors[] = 'Choose an active destination country.';
        }

        if (blank($product->name) || blank($product->category) || blank($product->entry_type)) {
            $errors[] = 'Name, category, and entry type are required.';
        }

        if ($product->effective_from && $product->effective_until && $product->effective_until->lte($product->effective_from)) {
            $errors[] = 'The effective-until date must be after the effective-from date.';
        }

        $activeProcessing = $product->processingOptions->where('is_active', true);
        if ($activeProcessing->isEmpty()) {
            $errors[] = 'Add at least one active processing option.';
        }

        if ($activeProcessing->contains(fn ($option): bool => $option->maximum_business_days < $option->minimum_business_days)) {
            $errors[] = 'Every processing option maximum must be greater than or equal to its minimum.';
        }

        $activeFees = $product->fees->where('is_active', true);
        if ($activeFees->isEmpty()) {
            $errors[] = 'Add at least one active fee component, including a zero-valued component for a genuinely free product.';
        }

        if ($activeFees->contains(fn ($fee): bool => strlen((string) $fee->currency) !== 3 || strtoupper((string) $fee->currency) !== $fee->currency)) {
            $errors[] = 'Every active fee must use an uppercase three-letter currency code.';
        }

        if ($activeFees->contains(fn ($fee): bool => $fee->payee === 'authority' && $fee->pay_online)) {
            $errors[] = 'Authority-direct fees cannot be included in TravelWheel online checkout.';
        }

        if ($product->eligibility_mode === VisaEligibilityMode::Rules) {
            $hasPositiveRule = $product->eligibilityRules
                ->where('is_active', true)
                ->contains(fn ($rule): bool => in_array($rule->rule_type, ['include_country', 'include_group'], true));

            if (! $hasPositiveRule) {
                $errors[] = 'Rule-based eligibility requires at least one active country or country-group inclusion.';
            }
        }

        if ($product->requirements->where('is_active', true)->isEmpty()) {
            $errors[] = 'Add at least one active requirement or explicitly add a “No documents required” requirement.';
        }

        return $errors;
    }

    public function publish(VisaProduct $product): VisaProduct
    {
        $errors = $this->errors($product);
        if ($errors !== []) {
            throw ValidationException::withMessages(['publication' => $errors]);
        }

        return DB::transaction(function () use ($product): VisaProduct {
            $product->forceFill([
                'publication_status' => VisaPublicationStatus::Published,
                'published_at' => now(),
            ])->save();

            return $product->refresh();
        });
    }

    public function unpublish(VisaProduct $product): VisaProduct
    {
        $product->forceFill([
            'publication_status' => VisaPublicationStatus::Draft,
            'published_at' => null,
        ])->save();

        return $product->refresh();
    }
}
