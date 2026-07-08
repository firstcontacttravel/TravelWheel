<?php

namespace App\Services;

use App\Mail\TravelFlexApplicationMail;
use App\Mail\TravelFlexRepaymentReminderMail;
use App\Mail\TravelFlexStatusMail;
use App\Models\FlightBooking;
use App\Models\TravelFlexApplication;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

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
                $key => $this->resolveDocumentPath($path),
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

    public function notifyCustomerStatus(TravelFlexApplication $application, string $status, ?string $note = null): bool
    {
        $email = $this->customerEmail($application);

        if (blank($email)) {
            return false;
        }

        Mail::to($email)->send(new TravelFlexStatusMail($application->fresh(['booking']), $status, $note));

        return true;
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

            $cacheKey = implode(':', [
                'travelflex-reminder',
                $application->id,
                $index,
                $dueDate->format('Y-m-d'),
                now('Africa/Lagos')->format('Y-m-d'),
            ]);

            if (! Cache::add($cacheKey, true, now()->addDays(2))) {
                continue;
            }

            try {
                Mail::to($email)->send(new TravelFlexRepaymentReminderMail($application, $instalment, $timing));
                $sent++;
            } catch (\Throwable $exception) {
                Cache::forget($cacheKey);
                Log::error('TravelFlex repayment reminder failed', [
                    'application_id' => $application->id,
                    'booking_ref' => $application->booking_ref,
                    'error' => $exception->getMessage(),
                ]);
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

        $legacyPath = storage_path('app/' . ltrim($path, '/\\'));

        return is_file($legacyPath) ? $legacyPath : null;
    }
}
