<?php

namespace App\Services;

use App\Mail\VisaApplicationVendorMail;
use App\Models\User;
use App\Models\VisaApplication;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class VisaVendorDispatchService
{
    /**
     * Queues the application, with every upload attached, to each recipient
     * as a separate email so addresses are never shown to one another. With
     * no recipients given it goes to the product vendor's registered email.
     * Sending again is allowed on purpose; each send is audited.
     *
     * @param  list<string>|null  $recipients
     * @return list<string> the addresses it was queued to
     */
    public function send(VisaApplication $application, User $actor, ?array $recipients = null): array
    {
        $application->loadMissing(['product.vendor', 'travelers.nationalityCountry', 'travelers.passportIssuingCountry', 'answers.question']);
        $vendor = $application->product?->vendor;

        if ($recipients === null) {
            if (! $vendor) {
                throw ValidationException::withMessages(['vendor' => 'Assign a vendor to this visa product before sending the application.']);
            }
            if (! $vendor->is_active) {
                throw ValidationException::withMessages(['vendor' => 'The assigned vendor is inactive. Activate it or choose another vendor.']);
            }
            $recipients = [$vendor->email];
        }

        $recipients = collect($recipients)->map(fn ($email) => strtolower(trim((string) $email)))->filter()->unique()->values()->all();
        if ($recipients === []) {
            throw ValidationException::withMessages(['other_recipients' => 'Add at least one email address.']);
        }
        Validator::make(['recipients' => $recipients], ['recipients.*' => ['email:rfc']], ['recipients.*.email' => ':input is not a valid email address.'])->validate();

        $toVendor = $vendor && in_array(strtolower((string) $vendor->email), $recipients, true);
        foreach ($recipients as $email) {
            $name = $toVendor && $email === strtolower((string) $vendor->email) ? ($vendor->contact_person ?: $vendor->name) : null;
            Mail::to($email, $name)->queue(new VisaApplicationVendorMail($application));
        }

        $application->auditEvents()->create([
            'user_id' => $actor->id,
            'event_type' => 'sent_to_vendor',
            'summary' => 'Application queued to '.implode(', ', $recipients).'.',
            'metadata' => [
                'vendor_id' => $vendor?->id,
                'vendor_name' => $vendor?->name,
                'recipients' => $recipients,
                'includes_registered_vendor_email' => $toVendor,
                'application_document_count' => $application->documents()->count(),
                'additional_document_count' => $application->additionalDocumentRequests()->whereNotNull('path')->count(),
            ],
        ]);

        return $recipients;
    }
}
