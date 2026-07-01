<?php

namespace App\Services;

use App\Models\Country;
use App\Models\VisaProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LegacyVisaCatalogueImporter
{
    public function import(): array
    {
        if (! Schema::hasTable('visas') || ! Schema::hasTable('visa_products')) {
            return ['standard' => 0, 'voa' => 0];
        }

        return DB::transaction(fn (): array => [
            'standard' => $this->importStandardVisas(),
            'voa' => $this->importVoa(),
        ]);
    }

    private function importStandardVisas(): int
    {
        $count = 0;

        foreach (DB::table('visas')->orderBy('id')->get() as $legacy) {
            $destination = Country::query()->find($legacy->country_id);
            if (! $destination || ! $destination->is_active) {
                continue;
            }

            $product = VisaProduct::query()->updateOrCreate(
                ['slug' => 'legacy-visa-'.$legacy->id],
                [
                    'destination_country_id' => $destination->id,
                    'name' => trim($destination->name.' '.$legacy->visa_type.' Visa'),
                    'family' => 'standard',
                    'category' => Str::lower($legacy->visa_type ?: 'visitor'),
                    'entry_type' => Str::lower($legacy->visa_category ?: 'single'),
                    'publication_status' => 'published',
                    'eligibility_mode' => 'all',
                    'validity_days' => $legacy->validity_days,
                    'summary' => $legacy->note ?: 'Visa assistance for travel to '.$destination->name.'.',
                    'important_notes' => $legacy->note,
                    'published_at' => now(),
                ]
            );

            $this->replaceRelations($product);
            $processing = $product->processingOptions()->create([
                'name' => $legacy->processing_type ?: 'Standard',
                'minimum_business_days' => max(1, (int) $legacy->processing_days),
                'maximum_business_days' => max(1, (int) $legacy->processing_days),
                'is_active' => true,
            ]);

            $sort = 0;
            foreach (['adult', 'child', 'infant'] as $travelerType) {
                $this->fee($product, $processing->id, 'Visa fee', 'visa', $travelerType, $legacy->currency, $legacy->{'visa_fee_'.$travelerType}, $legacy->pay_visa_to_embassy, ++$sort);
                $this->fee($product, $processing->id, 'Biometrics fee', 'biometrics', $travelerType, $legacy->currency, $legacy->{'biometrics_fee_'.$travelerType}, $legacy->pay_bio_to_embassy, ++$sort);
            }
            $adminCurrency = strtoupper($legacy->currency) !== 'NGN' && (float) $legacy->admin_fee >= 10000 ? 'NGN' : $legacy->currency;
            $this->fee($product, $processing->id, 'TravelWheel service fee', 'service', 'all', $adminCurrency, $legacy->admin_fee, false, ++$sort, 'per_application');

            if (Schema::hasTable('other_charges')) {
                foreach (DB::table('other_charges')->where('visa_id', $legacy->id)->orderBy('id')->get() as $charge) {
                    $this->fee($product, $processing->id, $charge->charge_name, 'other', $charge->traveler_type ?: 'all', $legacy->currency, $charge->amount, $charge->pay_to_embassy, ++$sort, $charge->traveler_type === 'all' ? 'per_application' : 'per_traveler');
                }
            }

            $documents = Schema::hasTable('visa_documents')
                ? DB::table('visa_documents')->where('visa_id', $legacy->id)->orderBy('id')->get()
                : collect();
            foreach ($documents->unique('document_name')->values() as $index => $document) {
                $product->requirements()->create([
                    'name' => $document->document_name,
                    'category' => $document->category ?: 'supporting_document',
                    'scope' => 'traveler',
                    'requirement_state' => 'required',
                    'sort_order' => $index,
                    'is_active' => true,
                ]);
            }
            if ($documents->isEmpty()) {
                $product->requirements()->create(['name' => 'Valid passport', 'category' => 'identity', 'scope' => 'traveler', 'requirement_state' => 'required', 'is_active' => true]);
            }

            foreach (['flight', 'hotel', 'insurance'] as $service) {
                if ($legacy->{'requires_'.$service}) {
                    $product->optionalServices()->create([
                        'service_type' => $service,
                        'name' => 'TravelWheel '.Str::headline($service).' Assistance',
                        'description' => 'This visa configuration requires '.$service.' arrangements to be confirmed during the application.',
                        'is_active' => true,
                    ]);
                }
            }

            $count++;
        }

        return $count;
    }

    private function importVoa(): int
    {
        if (! Schema::hasTable('voas')) {
            return 0;
        }

        $nigeria = Country::query()->where('is_active', true)->where(fn ($query) => $query->where('alpha2', 'NG')->orWhere('code', 'NG'))->first();
        $eligible = DB::table('voas')->orderBy('id')->get()->filter(fn ($row) => Country::query()->whereKey($row->from_country_id)->where('is_active', true)->exists());
        if (! $nigeria || $eligible->isEmpty()) {
            return 0;
        }

        $product = VisaProduct::query()->updateOrCreate(
            ['slug' => 'legacy-nigeria-visa-on-arrival'],
            [
                'destination_country_id' => $nigeria->id,
                'name' => 'Nigeria Visa on Arrival',
                'family' => 'voa',
                'category' => 'business',
                'entry_type' => 'single',
                'publication_status' => 'published',
                'eligibility_mode' => 'rules',
                'validity_days' => 30,
                'maximum_stay_days' => 30,
                'summary' => 'Visa on Arrival pre-approval assistance for eligible travelers visiting Nigeria.',
                'processing_disclaimer' => 'Processing time is an estimate and begins after all required documents are accepted.',
                'issuance_disclaimer' => 'Final admission and visa issuance remain subject to Nigerian immigration approval.',
                'published_at' => now(),
            ]
        );

        $this->replaceRelations($product);
        $processing = $product->processingOptions()->create(['name' => 'Standard', 'minimum_business_days' => 2, 'maximum_business_days' => 5, 'is_active' => true]);

        foreach ($eligible as $index => $row) {
            $product->eligibilityRules()->create([
                'rule_type' => 'include_country',
                'country_id' => $row->from_country_id,
                'public_message' => 'Visa on Arrival is available for this nationality.',
                'sort_order' => $index,
                'is_active' => true,
            ]);
            $this->fee($product, $processing->id, 'Visa on Arrival fee', 'visa', 'all', 'USD', $row->visa_fee, false, $index, 'per_traveler', ['nationality_country_id' => $row->from_country_id]);
        }

        if (Schema::hasTable('voa_fees')) {
            foreach (DB::table('voa_fees')->orderBy('id')->get() as $index => $fee) {
                foreach ($eligible as $nationalityIndex => $nationality) {
                    $amount = $nationality->is_african_country ? $fee->amount_african : $fee->amount_non_african;
                    $traveler = match ($fee->fee_type) {
                        'processing_adult' => 'adult',
                        'processing_np' => 'minor_nigerian',
                        'processing_fp' => 'minor_foreign',
                        default => 'all',
                    };
                    $this->fee($product, $processing->id, Str::headline($fee->fee_type), $fee->fee_type, $traveler, 'USD', $amount, false, 100 + ($index * 10) + $nationalityIndex, 'per_traveler', ['nationality_country_id' => $nationality->from_country_id]);
                }
            }
        }

        if (Schema::hasTable('voa_requirements')) {
            foreach (DB::table('voa_requirements')->orderBy('id')->get() as $index => $requirement) {
                $product->requirements()->create([
                    'name' => $requirement->requirement_name,
                    'category' => 'supporting_document',
                    'scope' => 'traveler',
                    'requirement_state' => 'conditional',
                    'description' => 'Required for '.$requirement->requirement_type.' applicants.',
                    'conditions' => ['applicant_type' => $requirement->requirement_type],
                    'sort_order' => $index,
                    'is_active' => true,
                ]);
            }
        }

        return 1;
    }

    private function replaceRelations(VisaProduct $product): void
    {
        $product->eligibilityRules()->delete();
        $product->fees()->delete();
        $product->processingOptions()->delete();
        $product->requirements()->delete();
        $product->optionalServices()->delete();
    }

    private function fee(VisaProduct $product, int $processingId, string $name, string $type, string $traveler, string $currency, mixed $amount, bool $authorityDirect, int $sort, string $basis = 'per_traveler', array $conditions = []): void
    {
        $product->fees()->create([
            'visa_processing_option_id' => $processingId,
            'name' => $name,
            'fee_type' => $type,
            'traveler_type' => $traveler,
            'calculation_basis' => $basis,
            'currency' => strtoupper($currency),
            'amount' => (float) $amount,
            'payee' => $authorityDirect ? 'authority' : 'travelwheel',
            'pay_online' => ! $authorityDirect,
            'conditions' => $conditions ?: null,
            'sort_order' => $sort,
            'is_active' => true,
        ]);
    }
}
