<?php

namespace App\Services;

use App\Mail\TravelFlexApplicationMail;
use App\Models\FlightBooking;
use App\Models\TravelFlexApplication;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class TravelFlexApplicationService
{
    public function sendProviderEmail(TravelFlexApplication $application): void
    {
        $sent = app(DurableMailService::class)->sendNowOrStore(
            DurableMailService::TRAVELFLEX_PROVIDER,
            (string) config('mail.travelflex_provider', 'loans@travelwheel.com'),
            $application,
            [],
            'travelflex-provider:'.$application->id,
            [(string) config('mail.travelwheel_address', config('mail.from.address'))],
        );

        $application->update([
            'provider_status' => $sent ? 'sent' : 'pending_retry',
            'provider_email_sent_at' => $sent ? now() : null,
            'provider_email_error' => $sent ? null : 'Email saved for automatic retry.',
        ]);

        if (! $sent) {
            throw new RuntimeException('Provider email was saved for automatic retry.');
        }
    }

    public function providerMailable(TravelFlexApplication $application): TravelFlexApplicationMail
    {
        $applicant = array_merge(
            $application->applicant_details ?? [],
            ['applicant_type' => $application->applicant_type ?? data_get($application->applicant_details, 'applicant_type', 'individual')],
            $application->identity_details ?? [],
            $application->employment_details ?? [],
        );
        $applicant['bank_details'] = $application->bank_details ?? [];
        $applicant['next_of_kin_details'] = $application->next_of_kin_details ?? [];
        $applicant['company_details'] = $application->company_details ?? [];
        $applicant['representative_details'] = $application->representative_details ?? [];
        $applicant['agreement_acceptance'] = $application->agreement_acceptance ?? [];
        $bvnLastFour = (string) data_get($application->bvn_metadata, 'last_four', '');
        $applicant['bvn'] = $bvnLastFour !== '' ? '*******'.$bvnLastFour : null;

        $flightInfo = $application->booking?->flight_snapshot ?? [];

        $uploadPaths = collect($application->document_paths ?? [])
            ->mapWithKeys(fn (?string $path, string $key): array => [
                $key => $this->resolveDocumentPath($path),
            ])
            ->all();

        return new TravelFlexApplicationMail(
            $applicant,
            $application->repayment_plan ?? [],
            $flightInfo,
            $uploadPaths,
            $application->booking_ref ?: $application->unique_id ?: '',
            $application,
        );
    }

    public function notifyCustomerStatus(TravelFlexApplication $application, string $status, ?string $note = null): bool
    {
        $email = $this->customerEmail($application);

        if (blank($email)) {
            return false;
        }

        return app(DurableMailService::class)->sendNowOrStore(
            DurableMailService::TRAVELFLEX_STATUS,
            $email,
            $application,
            compact('status', 'note'),
            "travelflex-status:{$application->id}:{$status}",
        );
    }

    public function syncPaymentFromBooking(FlightBooking $booking): ?TravelFlexApplication
    {
        $application = TravelFlexApplication::query()
            ->where('flight_booking_id', $booking->id)
            ->when(filled($booking->booking_ref), fn ($query) => $query->orWhere('booking_ref', $booking->booking_ref))
            ->latest()
            ->first();

        if (! $application) {
            return null;
        }

        $application->update([
            'payment_status' => $booking->payment_status === 'partially_paid' ? 'paid' : $booking->payment_status,
            'payment_method' => $booking->payment_method ?: $application->payment_method,
        ]);

        return $application->fresh(['booking']);
    }

    public function sendRepaymentReminders(): int
    {
        $sent = 0;

        TravelFlexApplication::query()
            ->where('payment_status', 'paid')
            ->whereIn('application_status', ['reviewed', 'approved'])
            ->whereNotNull('repayment_plan')
            ->chunkById(100, function ($applications) use (&$sent): void {
                foreach ($applications as $application) {
                    $sent += $this->sendDueRepaymentRemindersFor($application);
                }
            });

        return $sent;
    }

    private function sendDueRepaymentRemindersFor(TravelFlexApplication $application): int
    {
        $email = $this->customerEmail($application);
        $schedule = data_get($application->repayment_plan, 'schedule', []);

        if (blank($email) || ! is_array($schedule) || $schedule === []) {
            return 0;
        }

        $sent = 0;

        foreach ($schedule as $index => $instalment) {
            $dueDate = $this->instalmentDueDate($instalment);

            if (! $dueDate) {
                continue;
            }

            $daysUntilDue = now('Africa/Lagos')->startOfDay()->diffInDays($dueDate->copy()->startOfDay(), false);
            $timing = match (true) {
                $daysUntilDue === 3 => 'due in 3 days',
                $daysUntilDue === 1 => 'due tomorrow',
                $daysUntilDue === 0 => 'due today',
                $daysUntilDue < 0 => 'overdue',
                default => null,
            };

            if (! $timing) {
                continue;
            }

            $uniqueKey = implode(':', [
                'travelflex-reminder',
                $application->id,
                $index,
                $dueDate->format('Y-m-d'),
                now('Africa/Lagos')->format('Y-m-d'),
            ]);

            if (app(DurableMailService::class)->sendNowOrStore(
                DurableMailService::TRAVELFLEX_REPAYMENT,
                $email,
                $application,
                compact('instalment', 'timing'),
                $uniqueKey,
            )) {
                $sent++;
            }
        }

        return $sent;
    }

    private function instalmentDueDate(array $instalment): ?Carbon
    {
        $value = $instalment['dueDate'] ?? $instalment['due_date'] ?? $instalment['date'] ?? null;

        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value, 'Africa/Lagos');
        } catch (\Throwable) {
            return null;
        }
    }

    private function customerEmail(TravelFlexApplication $application): ?string
    {
        return data_get($application->applicant_details, 'email')
            ?: $application->booking?->contact_email;
    }

    private function resolveDocumentPath(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (is_file($path)) {
            return $path;
        }

        $localPath = Storage::disk('local')->path($path);

        if (is_file($localPath)) {
            return $localPath;
        }

        $legacyPath = storage_path('app/'.ltrim($path, '/\\'));

        return is_file($legacyPath) ? $legacyPath : null;
    }
}
