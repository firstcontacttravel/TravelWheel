<?php

namespace App\Services;

use App\Data\VisaEligibilityResult;
use App\Enums\VisaEligibilityMode;
use App\Models\Country;
use App\Models\VisaEligibilityRule;
use App\Models\VisaProduct;

class VisaEligibilityService
{
    public function evaluate(VisaProduct $product, Country $nationality, ?Country $residence = null, array $context = []): VisaEligibilityResult
    {
        $product->loadMissing('eligibilityRules.countryGroup.countries');
        $rules = $product->eligibilityRules->where('is_active', true);

        $excluded = $rules->first(fn (VisaEligibilityRule $rule): bool => $this->matchesCountryRule($rule, $nationality, ['exclude_country', 'exclude_group']));
        if ($excluded) {
            return new VisaEligibilityResult('ineligible', [$excluded->public_message ?: 'This nationality is excluded from this visa product.'], [$excluded->id]);
        }

        if ($product->eligibility_mode === VisaEligibilityMode::Rules) {
            $inclusions = $rules->filter(fn (VisaEligibilityRule $rule): bool => in_array($rule->rule_type, ['include_country', 'include_group'], true));
            $included = $inclusions->first(fn (VisaEligibilityRule $rule): bool => $this->matchesCountryRule($rule, $nationality, ['include_country', 'include_group']));
            if (! $included) {
                return new VisaEligibilityResult('ineligible', ['This nationality is not included for this visa product.']);
            }
        }

        $residenceRules = $rules->where('rule_type', 'residence_country');
        if ($residenceRules->isNotEmpty()) {
            if (! $residence) {
                return new VisaEligibilityResult('conditionally_eligible', ['Country of residence is required to confirm eligibility.'], $residenceRules->pluck('id')->all());
            }
            if (! $residenceRules->contains(fn (VisaEligibilityRule $rule): bool => $rule->country_id === $residence->id)) {
                return new VisaEligibilityResult('ineligible', ['The applicant does not meet the residence-country requirement.'], $residenceRules->pluck('id')->all());
            }
        }

        $manualRules = $rules->where('rule_type', 'manual_review')->filter(fn (VisaEligibilityRule $rule): bool => $this->conditionsMatch($rule->conditions ?? [], $context));
        if ($manualRules->isNotEmpty()) {
            return new VisaEligibilityResult(
                'conditionally_eligible',
                $manualRules->map(fn (VisaEligibilityRule $rule): string => $rule->public_message ?: 'TravelWheel must review this application before confirming eligibility.')->values()->all(),
                $manualRules->pluck('id')->all(),
            );
        }

        return new VisaEligibilityResult('eligible');
    }

    private function matchesCountryRule(VisaEligibilityRule $rule, Country $country, array $types): bool
    {
        if (! in_array($rule->rule_type, $types, true)) {
            return false;
        }

        if ($rule->country_id) {
            return $rule->country_id === $country->id;
        }

        return $rule->countryGroup?->countries->contains('id', $country->id) ?? false;
    }

    private function conditionsMatch(array $conditions, array $context): bool
    {
        if ($conditions === []) {
            return true;
        }

        return collect($conditions)->every(fn ($expected, string $key): bool => data_get($context, $key) == $expected);
    }
}
