<?php

namespace App\Services;

use App\Mail\VisaApplicationVendorMail;
use App\Models\User;
use App\Models\VisaApplication;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class VisaVendorDispatchService
{
    public function send(VisaApplication $application, User $actor): void
    {
        $application->loadMissing(['product.vendor', 'travelers.nationalityCountry', 'travelers.passportIssuingCountry', 'answers.question']);
        $vendor = $application->product?->vendor;

        if (! $vendor) {
            throw ValidationException::withMessages(['vendor' => 'Assign a vendor to this visa product before sending the application.']);
        }

        if (! $vendor->is_active) {
            throw ValidationException::withMessages(['vendor' => 'The assigned vendor is inactive. Activate it or choose another vendor.']);
        }

        Mail::to($vendor->email, $vendor->contact_person ?: $vendor->name)
            ->queue(new VisaApplicationVendorMail($application));

        $application->auditEvents()->create([
            'user_id' => $actor->id,
            'event_type' => 'sent_to_vendor',
            'summary' => 'Application queued for '.$vendor->name.'.',
            'metadata' => [
                'vendor_id' => $vendor->id,
                'vendor_name' => $vendor->name,
                'recipient' => $vendor->email,
                'application_document_count' => $application->documents()->count(),
                'additional_document_count' => $application->additionalDocumentRequests()->whereNotNull('path')->count(),
            ],
        ]);
    }
}
