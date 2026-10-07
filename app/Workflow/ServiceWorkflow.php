<?php

namespace App\Workflow;

use App\Models\WorkItem;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * A booking that is paid at checkout and then fulfilled by staff: car hire,
 * transfers, lounge, protocol, air cargo, support requests, insurance.
 *
 * Two columns, two meanings. The payment column (written by checkout and by
 * Finance's Mark paid) says whether the customer paid. fulfilment_status
 * (written only by FulfilmentService) says what staff have done. The stage
 * reads both: unpaid bookings wait on the customer; paid ones sit at their
 * fulfilment step.
 */
abstract class ServiceWorkflow extends Workflow
{
    /** Payment values that mean the customer has paid, compared case-insensitively. */
    private const PAID = ['paid', 'successful', 'billed_with_main_fee', 'confirmed', 'completed'];

    private const FAILED = ['failed'];

    /** The column checkout writes the payment result into. */
    abstract protected function paymentColumn(): string;

    /** The value Mark paid writes into it, in the casing this service already uses. */
    abstract protected function paidValue(): string;

    /** Department slug that works this service. */
    abstract protected function department(): string;

    /** Filament resource whose view page is this booking's page. */
    abstract protected function resource(): string;

    /**
     * The fulfilment steps after payment, in order, and where each may go
     * next. "new" is the first; "cancelled" is always available and is not
     * listed here.
     *
     * @return array<string, array{label: string, state: string, next: list<string>}>
     */
    abstract protected function steps(): array;

    /** When the customer is served (pickup, travel date), if the booking says. */
    public function serviceDate(Model $subject): ?CarbonInterface
    {
        return null;
    }

    public function stages(): array
    {
        $department = $this->department();

        $stages = [
            'awaiting_payment' => ['label' => 'Awaiting payment', 'state' => WorkItem::STATE_WAITING, 'department' => $department],
            'payment_failed' => ['label' => 'Payment failed', 'state' => WorkItem::STATE_CANCELLED, 'department' => $department],
        ];

        foreach ($this->steps() as $key => $step) {
            $stages[$key] = ['label' => $step['label'], 'state' => $step['state'], 'department' => $department];
        }

        return $stages + [
            'cancelled' => ['label' => 'Cancelled', 'state' => WorkItem::STATE_CANCELLED, 'department' => $department],
        ];
    }

    public function stageFor(Model $subject): string
    {
        $fulfilment = (string) ($subject->getAttribute('fulfilment_status') ?: 'new');

        if ($fulfilment === 'cancelled') {
            return 'cancelled';
        }

        if ($this->isPaid($subject)) {
            return array_key_exists($fulfilment, $this->steps()) ? $fulfilment : array_key_first($this->steps());
        }

        return in_array(strtolower((string) $subject->getAttribute($this->paymentColumn())), self::FAILED, true)
            ? 'payment_failed'
            : 'awaiting_payment';
    }

    public function isPaid(Model $subject): bool
    {
        return in_array(strtolower((string) $subject->getAttribute($this->paymentColumn())), self::PAID, true);
    }

    /** Fulfilment steps this booking can move to now, as key => label. Empty until paid. */
    public function nextSteps(Model $subject): array
    {
        if (! $this->isPaid($subject) || $subject->getAttribute('fulfilment_status') === 'cancelled') {
            return [];
        }

        $steps = $this->steps();
        $current = $this->stageFor($subject);

        return collect($steps[$current]['next'] ?? [])
            ->mapWithKeys(fn (string $key) => [$key => $steps[$key]['label']])
            ->all();
    }

    /** Writes Mark paid's value into the payment column. */
    public function markPaid(Model $subject): void
    {
        $subject->setAttribute($this->paymentColumn(), $this->paidValue());
        $subject->save();
    }

    /** The day of service is the deadline for anything still to do before it. */
    public function dueAt(Model $subject, string $stage): ?CarbonInterface
    {
        return ($this->stages()[$stage]['state'] ?? null) === WorkItem::STATE_OPEN ? $this->serviceDate($subject) : null;
    }

    /**
     * These tables keep dates as free strings from the booking forms. A
     * value that does not parse means no due time, never an error.
     */
    protected function parseDate(mixed $date, mixed $time = null): ?CarbonInterface
    {
        if (blank($date)) {
            return null;
        }

        try {
            $at = \Illuminate\Support\Carbon::parse((string) $date, 'Africa/Lagos');
        } catch (\Throwable) {
            return null;
        }

        if (filled($time)) {
            try {
                $clock = \Illuminate\Support\Carbon::parse((string) $time, 'Africa/Lagos');
                $at->setTime($clock->hour, $clock->minute);
            } catch (\Throwable) {
                // A date with an unreadable time is still a date.
            }
        }

        // The forms mean Lagos time. Eloquent writes a datetime's wall clock
        // without converting it, so hand back the app's timezone or every due
        // time would be stored an hour out.
        return $at->setTimezone(config('app.timezone'));
    }

    /**
     * The value as stored, skipping the model's casts: a date cast throws on
     * the free text some of these forms saved.
     */
    protected function raw(Model $subject, string $column): mixed
    {
        return $subject->getAttributes()[$column] ?? null;
    }

    public function reference(Model $subject): string
    {
        $reference = $subject->getAttribute($this->referenceColumn());

        return filled($reference) ? (string) $reference : $this->label().' #'.$subject->getKey();
    }

    public function url(Model $subject): string
    {
        return ($this->resource())::getUrl('view', ['record' => $subject]);
    }
}
