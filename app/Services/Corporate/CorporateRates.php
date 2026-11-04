<?php

namespace App\Services\Corporate;

use App\Models\Company;
use App\Models\CompanyServiceCharge;
use App\Support\FlightMarkup;
use Illuminate\Support\Facades\DB;

/**
 * Reading and saving corporate service charges. With a company it is that
 * company's rates; with null it is the default corporate rates. A blank rate
 * has no row, so it falls back. See FlightMarkup::chargeFor().
 */
class CorporateRates
{
    /** @return array<string, array<string, float|null>> route category => cabin => the saved rate, if any */
    public function saved(?Company $company): array
    {
        $saved = CompanyServiceCharge::keyedFor($company?->id);
        $rates = [];

        foreach (FlightMarkup::publicCharges() as $category => $cabins) {
            foreach (array_keys($cabins) as $cabin) {
                $rates[$category][$cabin] = $saved[$category.'.'.$cabin] ?? null;
            }
        }

        return $rates;
    }

    /**
     * @param  array<string, array<string, mixed>>  $rates
     * @return list<string> "route category.cabin" for each saved rate above the public one
     */
    public function save(?Company $company, array $rates): array
    {
        $public = FlightMarkup::publicCharges();
        $above = [];

        DB::transaction(function () use ($company, $rates, $public, &$above): void {
            foreach ($public as $category => $cabins) {
                foreach ($cabins as $cabin => $publicCharge) {
                    $value = $rates[$category][$cabin] ?? null;
                    $row = ['company_id' => $company?->id, 'route_category' => $category, 'cabin' => $cabin];

                    if (blank($value)) {
                        CompanyServiceCharge::query()->where($row)->delete();

                        continue;
                    }

                    CompanyServiceCharge::query()->updateOrCreate($row, ['amount' => (float) $value]);

                    if ((float) $value > $publicCharge) {
                        $above[] = $category.'.'.$cabin;
                    }
                }
            }
        });

        FlightMarkup::forgetCachedConfiguration();

        return $above;
    }
}
