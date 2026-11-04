<?php

namespace App\Services;

use App\Mail\TravelFlexApplicationMail;
use App\Models\FlightBooking;
use App\Models\NotificationOutbox;
use App\Models\TravelFlexApplication;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class TravelFlexApplicationService
{
    public const STAGE_DUE_IN_3_DAYS = 'due_in_3_days';

    public const STAGE_DUE_TOMORROW = 'due_tomorrow';

    public const STAGE_DUE_TODAY = 'due_today';

    public const STAGE_OVERDUE_FIRST = 'overdue_first';

    public const STAGE_OVERDUE_FINAL = 'overdue_final';

    public const REMINDERS_STOP_AFTER_DAYS = 14;

    private const DEAD_BOOKING_STATUSES = ['cancelled', 'failed', 'hold_expired_review', 'ticketing_failed'];

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

    /**
     * @return array{reminders: int, alerts: int} Emails that went out or were already sent.
     */
    public function sendRepaymentReminders(): array
    {
        $mail = app(DurableMailService::class);
        $counts = ['reminders' => 0, 'alerts' => 0];

        foreach ($this->remindableInstalments() as $row) {
            if ($row['stage'] && filled($row['email']) && $mail->sendNowOrStore(
                DurableMailService::TRAVELFLEX_REPAYMENT,
                $row['email'],
                $row['application'],
                ['instalment' => $row['instalment'], 'stage' => $row['stage']],
                $row['unique_key'],
            )) {
                $counts['reminders']++;
            }

            if ($this->needsOverdueAlert($row) && $this->sendOverdueAlert($row)) {
                $counts['alerts']++;
            }
        }

        return $counts;
    }

    /**
     * What a reminder run would do right now, without sending anything.
     *
     * @return array{send: list<array>, alerts: list<array>, too_late: list<array>}
     */
    public function previewRepaymentReminders(): array
    {
        $preview = ['send' => [], 'alerts' => [], 'too_late' => []];

        foreach ($this->remindableInstalments() as $row) {
            if ($row['days_until_due'] <= -self::REMINDERS_STOP_AFTER_DAYS) {
                $preview['too_late'][] = $row;

                continue;
            }

            if ($row['stage'] && filled($row['email']) && ! $this->alreadySent($row['unique_key'])) {
                $preview['send'][] = $row;
            }

            if ($this->needsOverdueAlert($row) && ! $this->alreadySent($row['alert_key'])) {
                $preview['alerts'][] = $row;
            }
        }

        return $preview;
    }

    public static function overdueAlertKey(int $applicationId, int $index, string $dueDate): string
    {
        return "travelflex-overdue-alert:{$applicationId}:{$index}:{$dueDate}";
    }

    /** The alert goes out alongside the customer's final reminder, whether or not they could be emailed. */
    private function needsOverdueAlert(array $row): bool
    {
        return $row['stage'] === self::STAGE_OVERDUE_FINAL;
    }

    private function sendOverdueAlert(array $row): bool
    {
        $recipients = array_values(array_unique(array_filter([
            (string) config('mail.travelflex_provider'),
            (string) config('mail.travelwheel_address'),
        ])));

        return app(DurableMailService::class)->sendNowOrStore(
            DurableMailService::TRAVELFLEX_OVERDUE_ALERT,
            $recipients[0] ?? '',
            $row['application'],
            ['instalment' => $row['instalment']],
            $row['alert_key'],
            array_slice($recipients, 1),
        );
    }

    private function alreadySent(string $uniqueKey): bool
    {
        return NotificationOutbox::query()
            ->where('unique_key', $uniqueKey)
            ->whereNotNull('sent_at')
            ->exists();
    }

    /**
     * Every instalment on a live repayment plan, with the reminder stage it is
     * in today. A plan is live once financing is approved and the deposit is
     * in, for as long as the booking hasn't fallen through.
     */
    private function remindableInstalments(): \Generator
    {
        $applications = TravelFlexApplication::query()
            ->with(['booking', 'instalmentPayments'])
            ->where('payment_status', 'paid')
            ->where('deposit_status', 'paid')
            ->where('application_status', 'approved')
            ->where('financing_status', 'approved')
            ->whereNotNull('repayment_plan')
            ->whereHas('booking', fn ($query) => $query->whereNotIn('booking_status', self::DEAD_BOOKING_STATUSES))
            ->lazyById(100);

        foreach ($applications as $application) {
            $email = $this->customerEmail($application);

            foreach (app(TravelFlexRepaymentService::class)->schedule($application) as $index => $row) {
                $dueDate = $row['due_date'];
                $instalment = $row['instalment'];

                if (! $dueDate || $row['status'] === TravelFlexRepaymentService::STATUS_PAID) {
                    continue;
                }

                // diffInDays returns a float on Carbon 3, which never matches an int with ===.
                $daysUntilDue = (int) now('Africa/Lagos')->startOfDay()->diffInDays($dueDate->copy()->startOfDay(), false);
                $stage = $this->reminderStage($daysUntilDue);

                yield [
                    'application' => $application,
                    'email' => $email,
                    'instalment' => $instalment,
                    'due_date' => $dueDate,
                    'days_until_due' => $daysUntilDue,
                    'stage' => $stage,
                    // One email per stage, so nothing here may change from day to day.
                    'unique_key' => implode(':', [
                        'travelflex-reminder',
                        $application->id,
                        $index,
                        $dueDate->format('Y-m-d'),
                        $stage,
                    ]),
                    'alert_key' => self::overdueAlertKey($application->id, (int) $index, $dueDate->format('Y-m-d')),
                ];
            }
        }
    }

    /**
     * The overdue stages are ranges so a day the scheduler misses can't lose
     * an email: the next run still sends it, and the key allows only one.
     */
    private function reminderStage(int $daysUntilDue): ?string
    {
        return match (true) {
            $daysUntilDue === 3 => self::STAGE_DUE_IN_3_DAYS,
            $daysUntilDue === 1 => self::STAGE_DUE_TOMORROW,
            $daysUntilDue === 0 => self::STAGE_DUE_TODAY,
            $daysUntilDue >= -6 && $daysUntilDue <= -1 => self::STAGE_OVERDUE_FIRST,
            $daysUntilDue >= -13 && $daysUntilDue <= -7 => self::STAGE_OVERDUE_FINAL,
            default => null,
        };
    }

    public function customerEmail(TravelFlexApplication $application): ?string
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
