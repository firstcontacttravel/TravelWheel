<?php

namespace App\Services;

use App\Models\VisaApplication;
use App\Models\VisaProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VisaApplicationDraftService
{
    public function start(VisaProduct $product, array $search, array $eligibleResultIds): array
    {
        if (! in_array($product->id, array_map('intval', $eligibleResultIds), true) || ! $product->newQuery()->whereKey($product)->currentlyPublished()->exists()) {
            throw ValidationException::withMessages(['visa' => 'This visa option is no longer available. Please run a new search.']);
        }

        $product->load(['processingOptions' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'), 'requirements' => fn ($query) => $query->where('is_active', true), 'questions' => fn ($query) => $query->where('is_active', true), 'optionalServices' => fn ($query) => $query->where('is_active', true)]);
        $plainToken = Str::random(64);
        $formConfiguration = app(VisaFormWorkflow::class)->snapshot(
            $product->form_configuration,
            $product->questions->isNotEmpty(),
            $product->optionalServices->isNotEmpty(),
            $product->requirements->isNotEmpty(),
        );

        $application = DB::transaction(function () use ($product, $search, $plainToken, $formConfiguration): VisaApplication {
            $application = VisaApplication::query()->create([
                'reference' => (string) Str::ulid(),
                'resume_token_hash' => hash('sha256', $plainToken),
                'visa_product_id' => $product->id,
                'visa_processing_option_id' => $product->processingOptions->first()?->id,
                'product_version' => $product->version,
                'status' => 'draft',
                'nationality_country_id' => $search['nationality_id'],
                'residence_country_id' => $search['residence_country_id'] ?? null,
                'destination_country_id' => $search['destination_id'] ?? null,
                'visa_destination_id' => $search['visa_destination_id'] ?? null,
                'arrival_date' => $search['arrival_date'],
                'departure_date' => $search['departure_date'],
                'adult_count' => $search['adults'],
                'child_count' => $search['children'],
                'infant_count' => $search['infants'],
                'search_snapshot' => $search,
                'product_snapshot' => ['id' => $product->id, 'version' => $product->version, 'name' => $product->name, 'family' => $product->family->value, 'requirements' => $product->requirements->pluck('id')->all(), 'questions' => $product->questions->pluck('id')->all()],
                'form_configuration' => $formConfiguration,
                'last_activity_at' => now(),
                'expires_at' => now()->addDays(30),
            ]);

            foreach (['adult', 'child', 'infant'] as $type) {
                for ($position = 1; $position <= (int) $search[Str::plural($type)]; $position++) {
                    $application->travelers()->create(['reference' => (string) Str::ulid(), 'traveler_type' => $type, 'applicant_type' => $type === 'adult' ? 'individual' : null, 'position' => $position, 'nationality_country_id' => $search['nationality_id']]);
                }
            }

            $application->statusHistory()->create(['to_status' => 'draft', 'actor_type' => 'applicant', 'reason' => 'Application started']);

            return $application;
        });

        return [$application, $plainToken];
    }

    public function authorize(VisaApplication $application, ?string $token = null): bool
    {
        if (! in_array($application->status, ['draft', 'awaiting_payment'], true) || $application->expires_at->isPast()) {
            return false;
        }

        if ($token && hash_equals($application->resume_token_hash, hash('sha256', $token))) {
            session()->put("visa_application_access.{$application->reference}", true);
        }

        return (bool) session("visa_application_access.{$application->reference}");
    }

    public function touch(VisaApplication $application, int $step, int $completedStep): void
    {
        $application->forceFill([
            'current_step' => $step,
            'completed_step' => max($application->completed_step, $completedStep),
            'last_activity_at' => now(),
            'expires_at' => now()->addDays(30),
        ])->save();
    }
}
