<?php

namespace App\Services;

use App\Mail\VendorApplicationDecisionMail;
use App\Models\User;
use App\Models\VendorApplication;
use App\Models\VisaVendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

/**
 * The team's review of a vendor application, in the order the paper form's
 * "Internal use only" box set out: documents verified, compliance
 * completed, then approved or rejected. More information can be requested
 * at any point before a decision.
 */
class VendorOnboardingService
{
    /**
     * Mark each document verified or rejected. The application moves on only
     * when none were rejected.
     *
     * @param  list<int>  $verifiedIds  documents that are in order; the rest are rejected
     * @return int how many documents were rejected
     */
    public function verifyDocuments(VendorApplication $application, array $verifiedIds, ?string $note, User $by): int
    {
        $this->assertOpen($application);

        return DB::transaction(function () use ($application, $verifiedIds, $note, $by) {
            $rejected = 0;

            foreach ($application->documents as $document) {
                $ok = in_array($document->id, $verifiedIds, false);
                $rejected += $ok ? 0 : 1;
                $document->update(['status' => $ok ? 'verified' : 'rejected', 'review_note' => $ok ? null : $note]);
            }

            if ($rejected === 0) {
                $application->update([
                    'status' => 'documents_verified',
                    'documents_verified_at' => now(),
                    'documents_verified_by' => $by->id,
                ]);
            }

            return $rejected;
        });
    }

    public function completeCompliance(VendorApplication $application, string $riskRating, ?string $nextReviewOn, ?string $note, User $by): void
    {
        $this->assertStatus($application, ['documents_verified']);

        $application->update([
            'status' => 'compliance_completed',
            'compliance_completed_at' => now(),
            'compliance_completed_by' => $by->id,
            'risk_rating' => $riskRating,
            'next_review_on' => $nextReviewOn,
            'internal_notes' => $this->appendNote($application->internal_notes, $note, $by),
        ]);
    }

    /**
     * @param  list<string>  $services  the services approved, from those applied for
     * @return bool whether the vendor's email went out
     */
    public function approve(VendorApplication $application, array $services, ?string $note, ?string $nextReviewOn, User $by): bool
    {
        $this->assertStatus($application, ['compliance_completed']);

        $services = array_values(array_intersect($application->services ?? [], $services));
        if ($services === []) {
            throw new InvalidArgumentException('Approve at least one of the services the vendor applied for.');
        }

        DB::transaction(function () use ($application, $services, $note, $nextReviewOn, $by) {
            $application->assignVendorCode();
            $application->fill([
                'status' => 'approved',
                'approved_services' => $services,
                'approved_at' => now(),
                'approved_by' => $by->id,
                'decision_note' => $note,
                'next_review_on' => $nextReviewOn ?? $application->next_review_on,
            ])->save();

            // Visa vendors are picked from the Visa Vendors list, so put an approved one there
            if (in_array('visa', $services, true)) {
                VisaVendor::firstOrCreate(['email' => $application->booking_email], [
                    'name' => $application->trading_name ?: $application->registered_name,
                    'contact_person' => $application->contact_name,
                    'phone' => $application->operations_phone ?: $application->contact_phone,
                    'address' => $application->address,
                    'notes' => "Onboarded through vendor application {$application->reference} (Vendor ID {$application->vendor_code}).",
                    'is_active' => true,
                ]);
            }
        });

        return $this->email($application, 'approved');
    }

    public function reject(VendorApplication $application, string $reason, User $by): bool
    {
        $this->assertOpen($application);

        $application->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => $by->id,
            'decision_note' => $reason,
        ]);

        return $this->email($application, 'rejected');
    }

    public function requestInformation(VendorApplication $application, string $request): bool
    {
        $this->assertOpen($application);

        $application->update(['status' => 'pending', 'decision_note' => $request]);

        return $this->email($application, 'pending');
    }

    private function email(VendorApplication $application, string $decision): bool
    {
        try {
            Mail::to($application->contact_email)->send(new VendorApplicationDecisionMail($application, $decision));

            return true;
        } catch (\Throwable $e) {
            Log::error('Vendor decision email failed', ['reference' => $application->reference, 'decision' => $decision, 'error' => $e->getMessage()]);

            return false;
        }
    }

    private function appendNote(?string $existing, ?string $note, User $by): ?string
    {
        if (blank($note)) {
            return $existing;
        }

        $line = '['.now()->format('j M Y').', '.$by->name.'] '.trim($note);

        return blank($existing) ? $line : $existing."\n".$line;
    }

    private function assertOpen(VendorApplication $application): void
    {
        if ($application->isDecided()) {
            throw new InvalidArgumentException('This application has already been '.$application->status.'.');
        }
    }

    private function assertStatus(VendorApplication $application, array $statuses): void
    {
        if (! in_array($application->status, $statuses, true)) {
            throw new InvalidArgumentException('This step is not available while the application is "'.$application->statusLabel().'".');
        }
    }
}
