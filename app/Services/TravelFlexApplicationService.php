<?php

namespace App\Services;

use App\Mail\TravelFlexApplicationMail;
use App\Models\TravelFlexApplication;
use Illuminate\Support\Facades\Mail;

class TravelFlexApplicationService
{
    public function sendProviderEmail(TravelFlexApplication $application): void
    {
        $applicant = array_merge(
            $application->applicant_details ?? [],
            $application->employment_details ?? [],
        );

        $flightInfo = $application->booking?->flight_snapshot ?? [];

        $uploadPaths = collect($application->document_paths ?? [])
            ->mapWithKeys(fn (?string $path, string $key): array => [
                $key => $path ? storage_path('app/' . $path) : null,
            ])
            ->all();

        Mail::to(config('mail.travelflex_provider', 'loans@travelwheel.com'))
            ->cc(config('mail.travelwheel_address', config('mail.from.address')))
            ->send(new TravelFlexApplicationMail(
                $applicant,
                $application->repayment_plan ?? [],
                $flightInfo,
                $uploadPaths,
                $application->booking_ref ?: $application->unique_id ?: '',
            ));

        $application->update([
            'provider_status' => 'sent',
            'provider_email_sent_at' => now(),
            'provider_email_error' => null,
        ]);
    }
}
