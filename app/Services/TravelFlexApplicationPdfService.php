<?php

namespace App\Services;

use App\Models\TravelFlexApplication;
use Barryvdh\DomPDF\Facade\Pdf;

class TravelFlexApplicationPdfService
{
    public function generate(TravelFlexApplication $application): string
    {
        return Pdf::loadView('pdf.travelflex-application', $this->buildViewData($application->loadMissing('booking')))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'dpi' => 150,
                'enable_php' => false,
                'enable_javascript' => false,
            ])
            ->output();
    }

    public function buildViewData(TravelFlexApplication $application): array
    {
        $booking = $application->booking;
        $flight = $booking?->flight_snapshot ?? [];
        $segments = $flight['segments'] ?? [];
        $multiLegs = $flight['multiLegs'] ?? [];
        $route = $application->booking_ref ?: 'TravelFlex';

        if ($multiLegs !== []) {
            $route = collect($multiLegs)
                ->map(fn (array $leg): string => trim(($leg['from'] ?? '') . ' - ' . ($leg['to'] ?? '')))
                ->filter()
                ->implode(' / ');
        } elseif ($segments !== []) {
            $first = $segments[0] ?? [];
            $last = $segments[array_key_last($segments)] ?? [];
            $route = trim(($first['from'] ?? '') . ' - ' . ($last['to'] ?? '')) ?: $route;
        }

        return [
            'application' => $application,
            'booking' => $booking,
            'route' => $route,
            'flight' => $flight,
            'applicant' => $application->applicant_details ?? [],
            'identity' => $application->identity_details ?? [],
            'employment' => $application->employment_details ?? [],
            'bank' => $application->bank_details ?? [],
            'nextOfKin' => $application->next_of_kin_details ?? [],
            'company' => $application->company_details ?? [],
            'representative' => $application->representative_details ?? [],
            'agreement' => $application->agreement_acceptance ?? [],
            'plan' => $application->repayment_plan ?? [],
            'generatedAt' => now('Africa/Lagos'),
        ];
    }
}
