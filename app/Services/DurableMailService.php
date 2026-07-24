<?php

namespace App\Services;

use App\Mail\BookingPendingMail;
use App\Mail\ETicketMail;
use App\Mail\PaymentReceiptMail;
use App\Mail\TravelFlexRepaymentReminderMail;
use App\Mail\TravelFlexStatusMail;
use App\Mail\UnTicketedConfirmationAlert;
use App\Models\FlightBooking;
use App\Models\NotificationOutbox;
use App\Models\TravelFlexApplication;
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
            'cc' => array_values(array_filter($cc)),
            'status' => 'pending',
            'available_at' => now(),
        ];

        $message = $uniqueKey
            ? NotificationOutbox::query()->firstOrCreate(['unique_key' => $uniqueKey], $attributes)
            : NotificationOutbox::query()->create($attributes);

        if ($message->sent_at) {
            return true;
        }

        return $this->deliver($message);
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
                (string) ($payload['timing'] ?? 'due'),
            ),
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

    private function travelFlex(Model $model): TravelFlexApplication
    {
        if (! $model instanceof TravelFlexApplication) {
            throw new \RuntimeException('Durable email requires a TravelFlex application.');
        }

        return $model;
    }
}
