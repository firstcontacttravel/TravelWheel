<?php

namespace App\Services;

use App\Models\Country;
use App\Models\VisaProduct;
use Illuminate\Support\Collection;

class VisaDiscoveryService
{
    public function __construct(
        private readonly VisaEligibilityService $eligibility,
        private readonly VisaFeeEstimateService $fees,
    ) {}

    public function search(Country $nationality, Country $destination, ?Country $residence, array $travelers, array $context = []): Collection
    {
        return VisaProduct::query()
            ->currentlyPublished()
            ->where('destination_country_id', $destination->id)
            ->with([
                'destinationCountry',
                'eligibilityRules.countryGroup.countries',
                'processingOptions' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
                'fees' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
                'requirements' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
            ])
            ->get()
            ->map(function (VisaProduct $product) use ($nationality, $residence, $travelers, $context): array {
                $eligibility = $this->eligibility->evaluate($product, $nationality, $residence, $context);

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'family' => $product->family->value,
                    'category' => $product->category,
                    'entry_type' => $product->entry_type,
                    'summary' => $product->summary,
                    'validity_days' => $product->validity_days,
                    'maximum_stay_days' => $product->maximum_stay_days,
                    'processing_disclaimer' => $product->processing_disclaimer,
                    'issuance_disclaimer' => $product->issuance_disclaimer,
                    'eligibility' => ['status' => $eligibility->status, 'messages' => $eligibility->messages],
                    'requirements' => $product->requirements->map(fn ($requirement): array => [
                        'name' => $requirement->name,
                        'state' => $requirement->requirement_state,
                        'scope' => $requirement->scope,
                    ])->values()->all(),
                    'estimate' => $this->fees->estimate($product, $travelers, null, [
                        'nationality_country_id' => $nationality->id,
                    ]),
                ];
            })
            ->filter(fn (array $result): bool => in_array($result['eligibility']['status'], ['eligible', 'conditionally_eligible'], true))
            ->values();
    }
}
