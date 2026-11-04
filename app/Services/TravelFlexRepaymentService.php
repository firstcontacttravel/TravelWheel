<?php

namespace App\Services;

use App\Models\TravelFlexApplication;
use App\Models\TravelFlexInstalmentPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The record of which TravelFlex instalments have been repaid. The schedule
 * itself lives in the application's encrypted repayment_plan; payments are
 * matched to it by position and due date.
 */
class TravelFlexRepaymentService
{
    public const STATUS_PAID = 'paid';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_DUE = 'due';

    public const STATUS_UPCOMING = 'upcoming';

    /**
     * The plan's instalments with where each one stands today, keyed by their
     * position in the schedule. A row with no readable due date has no status
     * and can't be paid.
     *
     * @return array<int, array{index: int, instalment: array, label: string, due_date: ?Carbon, due_label: string, amount: float, status: ?string, payment: ?TravelFlexInstalmentPayment}>
     */
    public function schedule(TravelFlexApplication $application): array
    {
        $schedule = data_get($application->repayment_plan, 'schedule', []);

        if (! is_array($schedule)) {
            return [];
        }

        $payments = $application->instalmentPayments->reject->isReversed();
        $rows = [];

        foreach ($schedule as $index => $instalment) {
            if (! is_array($instalment)) {
                continue;
            }

            $dueDate = $this->dueDate($instalment);
            $payment = $dueDate ? $payments->first(fn (TravelFlexInstalmentPayment $payment): bool => $payment->instalment_index === (int) $index
                && $payment->due_date->format('Y-m-d') === $dueDate->format('Y-m-d')) : null;

            $rows[$index] = [
                'index' => (int) $index,
                'instalment' => $instalment,
                'label' => (string) ($instalment['label'] ?? $instalment['title'] ?? 'Instalment '.((int) $index + 1)),
                'due_date' => $dueDate,
                'due_label' => (string) ($instalment['dueDate'] ?? $dueDate?->toFormattedDateString() ?? '-'),
                'amount' => $this->amount($instalment),
                'status' => $this->status($dueDate, $payment),
                'payment' => $payment,
            ];
        }

        return $rows;
    }

    /**
     * Payments can come in on any approved plan with its deposit paid, even if
     * the booking has since fallen through and reminders have stopped.
     */
    public function canRecordPayments(TravelFlexApplication $application): bool
    {
        return $application->application_status === 'approved'
            && $application->financing_status === 'approved'
            && $application->deposit_status === 'paid';
    }

    public function isRepaidInFull(TravelFlexApplication $application): bool
    {
        $rows = $this->schedule($application);

        return $rows !== [] && collect($rows)->every(fn (array $row): bool => $row['status'] === self::STATUS_PAID);
    }

    /**
     * @return array<int, string> Unpaid instalments for a form's select, keyed by position.
     */
    public function unpaidOptions(TravelFlexApplication $application): array
    {
        if (! $this->canRecordPayments($application)) {
            return [];
        }

        return collect($this->schedule($application))
            ->filter(fn (array $row): bool => $row['due_date'] !== null && $row['status'] !== self::STATUS_PAID)
            ->map(fn (array $row): string => $row['label'].' · due '.$row['due_label'].' · NGN '.number_format($row['amount'], 2))
            ->all();
    }

    public function markPaid(
        TravelFlexApplication $application,
        int $index,
        string $paidOn,
        string $reference,
        ?string $note,
        User $recordedBy,
    ): TravelFlexInstalmentPayment {
        $paidOnDate = Carbon::parse($paidOn, 'Africa/Lagos')->startOfDay();

        if ($paidOnDate->isAfter(now('Africa/Lagos'))) {
            throw ValidationException::withMessages(['paid_on' => 'The date received cannot be in the future.']);
        }

        $payment = DB::transaction(function () use ($application, $index, $paidOnDate, $reference, $note, $recordedBy): TravelFlexInstalmentPayment {
            // Holding the application row stops two people recording the same instalment.
            $locked = TravelFlexApplication::query()->lockForUpdate()->findOrFail($application->getKey());

            if (! $this->canRecordPayments($locked)) {
                throw ValidationException::withMessages(['instalment_index' => 'Repayments can only be recorded on an approved plan with its deposit paid.']);
            }

            $row = $this->schedule($locked)[$index] ?? null;

            if (! $row || ! $row['due_date']) {
                throw ValidationException::withMessages(['instalment_index' => 'That instalment is not on this repayment plan.']);
            }

            if ($row['status'] === self::STATUS_PAID) {
                throw ValidationException::withMessages(['instalment_index' => "{$row['label']} is already marked paid."]);
            }

            return $locked->instalmentPayments()->create([
                'instalment_index' => $index,
                'due_date' => $row['due_date']->format('Y-m-d'),
                'label' => $row['label'],
                'amount' => $row['amount'],
                'paid_on' => $paidOnDate->format('Y-m-d'),
                'payment_reference' => $reference,
                'note' => filled($note) ? $note : null,
                'recorded_by' => $recordedBy->getKey(),
            ]);
        });

        $application->unsetRelation('instalmentPayments');

        $mail = app(DurableMailService::class);
        // A reminder or alert waiting on a retry must not go out once this is paid.
        $mail->cancelUnsent(implode(':', ['travelflex-reminder', $application->getKey(), $index, $payment->due_date->format('Y-m-d'), '']));
        $mail->cancelUnsent(TravelFlexApplicationService::overdueAlertKey($application->getKey(), $index, $payment->due_date->format('Y-m-d')));

        $email = app(TravelFlexApplicationService::class)->customerEmail($application);
        if (filled($email)) {
            $mail->sendNowOrStore(
                DurableMailService::TRAVELFLEX_INSTALMENT_PAID,
                $email,
                $payment,
                [],
                $this->confirmationKey($payment),
            );
        }

        return $payment;
    }

    /**
     * Undoes a payment recorded by mistake. Reminders for the instalment pick
     * up again from the next run if it is still within a stage.
     */
    public function reverse(TravelFlexInstalmentPayment $payment, string $reason, User $reversedBy): void
    {
        DB::transaction(function () use ($payment, $reason, $reversedBy): void {
            $locked = TravelFlexInstalmentPayment::query()->lockForUpdate()->findOrFail($payment->getKey());

            if ($locked->isReversed()) {
                throw ValidationException::withMessages(['payment_id' => 'That payment has already been reversed.']);
            }

            $locked->update([
                'reversed_at' => now(),
                'reversed_by' => $reversedBy->getKey(),
                'reversal_reason' => $reason,
            ]);
        });

        $payment->refresh();
        app(DurableMailService::class)->cancelUnsent($this->confirmationKey($payment));
    }

    public function dueDate(array $instalment): ?Carbon
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

    private function amount(array $instalment): float
    {
        return (float) ($instalment['amount'] ?? $instalment['total'] ?? $instalment['principal'] ?? 0);
    }

    private function status(?Carbon $dueDate, ?TravelFlexInstalmentPayment $payment): ?string
    {
        if ($payment) {
            return self::STATUS_PAID;
        }

        if (! $dueDate) {
            return null;
        }

        $daysUntilDue = (int) now('Africa/Lagos')->startOfDay()->diffInDays($dueDate->copy()->startOfDay(), false);

        return match (true) {
            $daysUntilDue < 0 => self::STATUS_OVERDUE,
            $daysUntilDue === 0 => self::STATUS_DUE,
            default => self::STATUS_UPCOMING,
        };
    }

    private function confirmationKey(TravelFlexInstalmentPayment $payment): string
    {
        return 'travelflex-instalment-paid:'.$payment->getKey();
    }
}
