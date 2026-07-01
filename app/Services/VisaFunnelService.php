<?php

namespace App\Services;

use App\Models\VisaApplication;
use App\Models\VisaFunnelEvent;
use Illuminate\Support\Str;

class VisaFunnelService
{
    public function record(string $event, array $metadata = [], ?VisaApplication $application = null, ?string $idempotencyKey = null): void
    {
        $journeyId = session('visa_journey_id');
        if (! $journeyId && $application) {
            $journeyId = VisaFunnelEvent::query()->where('visa_application_id', $application->id)->oldest()->value('journey_id');
        }
        if (! $journeyId) {
            $journeyId = (string) Str::uuid();
            session(['visa_journey_id' => $journeyId]);
        }

        $attributes = ['journey_id' => $journeyId, 'visa_application_id' => $application?->id, 'visa_product_id' => $application?->visa_product_id ?? ($metadata['visa_product_id'] ?? null), 'event' => $event, 'metadata' => $metadata];
        if ($idempotencyKey) {
            VisaFunnelEvent::query()->firstOrCreate(['idempotency_key' => hash('sha256', $idempotencyKey)], $attributes);
        } else {
            VisaFunnelEvent::query()->create($attributes);
        }
    }
}
