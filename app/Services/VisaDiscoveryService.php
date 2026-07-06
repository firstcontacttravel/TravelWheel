<?php

namespace App\Services;

use App\Models\Country;
use App\Models\VisaProduct;
use App\Models\VisaDestination;
use Illuminate\Support\Collection;

class VisaDiscoveryService
{
    public function __construct(
        private readonly VisaEligibilityService $eligibility,
        private readonly VisaFeeEstimateService $fees,
    ) {}

    public function search(Country $nationality, Country|VisaDestination $destination, ?Country $residence, array $travelers, array $context = []): Collection
    {
        $isCountry = $destination instanceof Country;

        return VisaProduct::query()
            ->currentlyPublished()
            ->where(function ($query) use ($destination, $isCountry): void {
                if ($isCountry) {
                    $query->where('destination_country_id', $destination->id);

                    return;
                }

                $query->where('visa_destination_id', $destination->id);
            })
            ->when(! $isCountry || $destination->alpha2 !== 'NG' || $nationality->alpha2 === 'NG', fn ($query) => $query->where('family', '!=', 'voa'))
            ->with([
                'destinationCountry',
                'destination.countries',
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
                    'regional_coverage' => $product->destination ? [
                        'name' => $product->destination->name,
                        'count' => $product->destination->countries->count(),
                        'countries' => $product->destination->countries
                            ->sortBy('name')
                            ->pluck('name')
                            ->values()
                            ->all(),
                    ] : null,
                    'eligibility' => ['status' => $eligibility->status, 'messages' => $eligibility->messages],
                    'requirements' => $product->requirements
                        ->filter(fn ($requirement): bool => $this->requirementMatchesTravelerMix($requirement->conditions ?? [], $travelers))
                        ->map(fn ($requirement): array => [
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

    private function requirementMatchesTravelerMix(array $conditions, array $travelers): bool
    {
        $applicantTypes = (array) ($conditions['applicant_type'] ?? []);

        if ($applicantTypes === []) {
            return true;
        }

        $hasAdult = (int) ($travelers['adult'] ?? 0) > 0;
        $hasMinor = (int) ($travelers['child'] ?? 0) + (int) ($travelers['infant'] ?? 0) > 0;

        return collect($applicantTypes)->contains(function (string $type) use ($hasAdult, $hasMinor): bool {
            return match ($type) {
                'minor_nigerian', 'minor_foreign' => $hasMinor,
                'individual', 'company' => $hasAdult,
                default => true,
            };
        });
    }
}
