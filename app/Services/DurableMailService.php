<?php

namespace App\Services;

use App\Mail\BookingPendingMail;
use App\Mail\ETicketMail;
use App\Mail\FlightSupplierAlertMail;
use App\Mail\PaymentReceiptMail;
use App\Mail\TravelFlexInstalmentPaidMail;
use App\Mail\TravelFlexOverdueAlertMail;
use App\Mail\TravelFlexRepaymentReminderMail;
use App\Mail\TravelFlexStatusMail;
use App\Mail\TravelFlexTicketBookedMail;
use App\Mail\UnTicketedConfirmationAlert;
use App\Mail\WorkDeadlineMail;
use App\Mail\WorkEscalationMail;
use App\Models\CompanyTraveller;
use App\Models\CompanyUser;
use App\Models\Escalation;
use App\Models\FlightBooking;
use App\Models\FlightSupplierEvent;
use App\Models\NotificationOutbox;
use App\Models\TravelFlexApplication;
use App\Models\TravelFlexInstalmentPayment;
use App\Models\WorkItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class DurableMailService
{
    public const FLIGHT_ETICKET = 'flight_eticket';

    public const FLIGHT_RECEIPT = 'flight_payment_receipt';

    public const FLIGHT_PENDING = 'flight_booking_pending';

    public const TICKETING_ALERT = 'flight_ticketing_alert';

    public const TRAVELFLEX_PROVIDER = 'travelflex_provider';

    public const TRAVELFLEX_STATUS = 'travelflex_status';

    public const TRAVELFLEX_REPAYMENT = 'travelflex_repayment';

    public const TRAVELFLEX_INSTALMENT_PAID = 'travelflex_instalment_paid';

    public const TRAVELFLEX_OVERDUE_ALERT = 'travelflex_overdue_alert';

    public const TRAVELFLEX_TICKET_BOOKED = 'travelflex_ticket_booked';

    public const FLIGHT_SUPPLIER_ALERT = 'flight_supplier_alert';

    public const WORK_ESCALATION = 'work_escalation';

    public const WORK_DEADLINE = 'work_deadline';

    public const STATUS_CANCELLED = 'cancelled';

    public function sendNowOrStore(
        string $kind,
        string $recipient,
        Model $related,
        array $payload = [],
        ?string $uniqueKey = null,
        array $cc = [],
    ): bool {
        if (blank($recipient)) {
            return false;
        }

        $attributes = [
            'kind' => $kind,
            'recipient' => $recipient,
            'related_type' => $related->getMorphClass(),
            'related_id' => $related->getKey(),
            'payload' => $payload,
            'cc' => array_values(array_filter(array_merge($cc, $this->companyAdminCopies($kind, $recipient, $related), $this->travellerCopies($kind, $recipient, $related)))),
            'status' => 'pending',
            'available_at' => now(),
        ];

        $message = $uniqueKey
            ? NotificationOutbox::query()->firstOrCreate(['unique_key' => $uniqueKey], $attributes)
            : NotificationOutbox::query()->create($attributes);

        if ($message->sent_at) {
            return true;
        }

        // Asking for a cancelled email again means it is wanted after all, e.g.
        // a reminder cancelled by a payment that has since been reversed.
        if ($message->status === self::STATUS_CANCELLED) {
            $message->forceFill(['status' => 'pending', 'last_error' => null])->save();
        }

        return $this->deliver($message);
    }

    /**
     * A company's Admins are copied on its flight booking emails. Done here
     * because several places send them: checkout, the admin and ticketing.
     *
     * @return list<string>
     */
    private function companyAdminCopies(string $kind, string $recipient, Model $related): array
    {
        if (! $related instanceof FlightBooking
            || ! $related->company_id
            || ! in_array($kind, [self::FLIGHT_ETICKET, self::FLIGHT_RECEIPT, self::FLIGHT_PENDING], true)) {
            return [];
        }

        return CompanyUser::query()
            ->where('company_id', $related->company_id)
            ->where('role', 'admin')
            ->whereNull('deactivated_at')
            ->where('email', '!=', strtolower(trim($recipient)))
            ->pluck('email')
            ->all();
    }

    /** Saved travellers with an email get a copy of their e-ticket. */
    private function travellerCopies(string $kind, string $recipient, Model $related): array
    {
        if ($kind !== self::FLIGHT_ETICKET || ! $related instanceof FlightBooking || ! $related->company_id) {
            return [];
        }

        $ids = collect((array) $related->passengers_snapshot)->pluck('saved_traveller_id')->filter()->all();

        if ($ids === []) {
            return [];
        }

        return CompanyTraveller::query()
            ->where('company_id', $related->company_id)
            ->whereIn('id', $ids)
            ->whereNotNull('email')
            ->pluck('email')
            ->reject(fn (string $email): bool => strtolower($email) === strtolower(trim($recipient)))
            ->values()
            ->all();
    }

    /**
     * Stops unsent emails whose unique key starts with the given prefix or
     * equals it, so a retry can't send something that no longer applies.
     */
    public function cancelUnsent(string $uniqueKeyPrefix): int
    {
        return NotificationOutbox::query()
            ->where('unique_key', 'like', addcslashes($uniqueKeyPrefix, '\\%_').'%')
            ->whereNull('sent_at')
            ->update([
                'status' => self::STATUS_CANCELLED,
                'failed_at' => null,
                'last_error' => null,
                'updated_at' => now(),
            ]);
    }

    /**
     * Records the email for the outbox to send on its next run, without
     * trying now. For internal alerts sent to several people at once, where
     * the person who triggered them should not wait on the mail server.
     */
    public function store(string $kind, string $recipient, Model $related, array $payload = [], ?string $uniqueKey = null): ?NotificationOutbox
    {
        if (blank($recipient)) {
            return null;
        }

        $attributes = [
            'kind' => $kind,
            'recipient' => $recipient,
            'related_type' => $related->getMorphClass(),
            'related_id' => $related->getKey(),
            'payload' => $payload,
            'cc' => [],
            'status' => 'pending',
            'available_at' => now(),
        ];

        return $uniqueKey
            ? NotificationOutbox::query()->firstOrCreate(['unique_key' => $uniqueKey], $attributes)
            : NotificationOutbox::query()->create($attributes);
    }

    public function deliver(NotificationOutbox $message): bool
    {
        $lock = Cache::lock('notification-outbox:'.$message->id, 120);
        if (! $lock->get()) {
            return false;
        }

        try {
            return $this->deliverUnlocked($message->fresh());
        } finally {
            $lock->release();
        }
    }

    private function deliverUnlocked(NotificationOutbox $message): bool
    {
        if ($message->sent_at) {
            return true;
        }

        // Cancelled while a retry run was already holding this row.
        if ($message->status === self::STATUS_CANCELLED) {
            return false;
        }

        $message->forceFill([
            'status' => 'processing',
            'attempts' => $message->attempts + 1,
            'last_attempted_at' => now(),
            'last_error' => null,
        ])->save();

        try {
            $related = $message->related;

            if (! $related) {
                throw new \RuntimeException('Related record no longer exists.');
            }

            $pending = Mail::to($message->recipient);
            if (($message->cc ?? []) !== []) {
                $pending->cc($message->cc);
            }
            $pending->send($this->mailable($message, $related));

            $message->forceFill([
                'status' => 'sent',
                'sent_at' => now(),
                'failed_at' => null,
                'last_error' => null,
            ])->save();

            return true;
        } catch (Throwable $exception) {
            $message->forceFill([
                'status' => 'failed',
                'failed_at' => now(),
                'available_at' => now()->addMinutes(min(60, max(1, $message->attempts * 5))),
                'last_error' => Str::limit($exception->getMessage(), 2000),
            ])->save();

            Log::warning('Durable email delivery deferred', [
                'outbox_id' => $message->id,
                'kind' => $message->kind,
                'related_type' => $message->related_type,
                'related_id' => $message->related_id,
                'attempts' => $message->attempts,
                'error_type' => $exception::class,
            ]);

            return false;
        }
    }

    public function processPending(int $limit = 100): array
    {
        $sent = 0;
        $failed = 0;

        NotificationOutbox::query()
            ->where('status', 'processing')
            ->where(function ($query): void {
                $query
                    ->whereNull('last_attempted_at')
                    ->orWhere('last_attempted_at', '<=', now()->subMinutes(10));
            })
            ->update([
                'status' => 'failed',
                'available_at' => now(),
                'failed_at' => now(),
                'last_error' => 'Delivery process stopped before completion; queued for a safe retry.',
                'updated_at' => now(),
            ]);

        NotificationOutbox::query()
            ->whereNull('sent_at')
            ->whereIn('status', ['pending', 'failed'])
            ->where(fn ($query) => $query->whereNull('available_at')->orWhere('available_at', '<=', now()))
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (NotificationOutbox $message) use (&$sent, &$failed): void {
                $this->deliver($message) ? $sent++ : $failed++;
            });

        return compact('sent', 'failed');
    }

    private function mailable(NotificationOutbox $message, Model $related): Mailable
    {
        $payload = $message->payload ?? [];

        return match ($message->kind) {
            self::FLIGHT_ETICKET => new ETicketMail(
                $this->flight($related),
                $payload['trip_details'] ?? $this->flight($related)->itinerary_snapshot ?? [],
            ),
            self::FLIGHT_RECEIPT => new PaymentReceiptMail($this->flight($related)),
            self::FLIGHT_PENDING => new BookingPendingMail(
                $this->flight($related),
                (string) ($payload['method'] ?? 'bank_transfer'),
            ),
            self::TICKETING_ALERT => new UnTicketedConfirmationAlert(
                app(AdminTicketingService::class)->failureAlertData(
                    $this->flight($related),
                    (string) ($payload['message'] ?? 'Ticketing requires manual review.'),
                ),
            ),
            self::TRAVELFLEX_PROVIDER => app(TravelFlexApplicationService::class)
                ->providerMailable($this->travelFlex($related)),
            self::TRAVELFLEX_STATUS => new TravelFlexStatusMail(
                $this->travelFlex($related)->fresh(['booking']),
                (string) ($payload['status'] ?? 'reviewed'),
                $payload['note'] ?? null,
            ),
            self::TRAVELFLEX_REPAYMENT => new TravelFlexRepaymentReminderMail(
                $this->travelFlex($related)->fresh(['booking']),
                (array) ($payload['instalment'] ?? []),
                (string) ($payload['stage'] ?? ''),
            ),
            self::TRAVELFLEX_INSTALMENT_PAID => new TravelFlexInstalmentPaidMail($this->instalmentPayment($related)),
            self::TRAVELFLEX_OVERDUE_ALERT => new TravelFlexOverdueAlertMail(
                $this->travelFlex($related)->fresh(['booking', 'instalmentPayments']),
                (array) ($payload['instalment'] ?? []),
            ),
            self::TRAVELFLEX_TICKET_BOOKED => new TravelFlexTicketBookedMail($this->flight($related)),
            self::FLIGHT_SUPPLIER_ALERT => new FlightSupplierAlertMail($this->supplierEvent($related)),
            self::WORK_ESCALATION => new WorkEscalationMail($this->escalation($related), (string) ($payload['event'] ?? 'raised')),
            self::WORK_DEADLINE => new WorkDeadlineMail($this->workItem($related), (string) ($payload['event'] ?? 'missed')),
            default => throw new \RuntimeException("Unsupported durable email kind [{$message->kind}]."),
        };
    }

    private function flight(Model $model): FlightBooking
    {
        if (! $model instanceof FlightBooking) {
            throw new \RuntimeException('Durable email requires a flight booking.');
        }

        return $model;
    }

    private function supplierEvent(Model $model): FlightSupplierEvent
    {
        if (! $model instanceof FlightSupplierEvent) {
            throw new \RuntimeException('Durable email requires a flight supplier event.');
        }

        return $model;
    }

    private function travelFlex(Model $model): TravelFlexApplication
    {
        if (! $model instanceof TravelFlexApplication) {
            throw new \RuntimeException('Durable email requires a TravelFlex application.');
        }

        return $model;
    }

    private function instalmentPayment(Model $model): TravelFlexInstalmentPayment
    {
        if (! $model instanceof TravelFlexInstalmentPayment) {
            throw new \RuntimeException('Durable email requires a TravelFlex instalment payment.');
        }

        return $model;
    }

    private function workItem(Model $model): WorkItem
    {
        if (! $model instanceof WorkItem) {
            throw new \RuntimeException('Durable email requires a work item.');
        }

        return $model;
    }

    private function escalation(Model $model): Escalation
    {
        if (! $model instanceof Escalation) {
            throw new \RuntimeException('Durable email requires an escalation.');
        }

        return $model;
    }
}
