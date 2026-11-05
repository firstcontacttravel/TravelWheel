<?php

namespace App\Models;

use App\Services\GroundBookingService;
use App\Services\LoungeBookingService;
use App\Services\SupportBookingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * A booking staff made from the admin for a customer. Its payment state is the
 * product's own (payment_status on the booking), so a payment through the link
 * and a recorded one can't disagree.
 */
class StaffBooking extends Model
{
    /** Ways Finance or the CEO can record a payment by hand. */
    public const RECORDED_METHODS = [
        'bank_transfer' => 'Bank transfer',
        'pos' => 'POS',
        'cash' => 'Cash',
    ];

    /** Per product: its name, the SeerBit callback that confirms a payment, and what completes one. */
    public const PRODUCTS = [
        CarHire::class => ['label' => 'Car Hire', 'seerbit_route' => 'air.carhire.seerbit.callback', 'service' => GroundBookingService::class],
        Transfer::class => ['label' => "Pick up 'n' Drop off", 'seerbit_route' => 'air.transfer.seerbit.callback', 'service' => GroundBookingService::class],
        SupportFlightAssist::class => ['label' => 'Flight Assist', 'seerbit_route' => 'air.support.seerbit.callback', 'service' => SupportBookingService::class],
        SupportYellowCard::class => ['label' => 'Yellow Card Assistance', 'seerbit_route' => 'air.support.seerbit.callback', 'service' => SupportBookingService::class],
        LoungeBooking::class => ['label' => 'Lounge', 'seerbit_route' => 'air.lounge.callback', 'service' => LoungeBookingService::class],
    ];

    protected $fillable = [
        'bookable_type',
        'bookable_id',
        'created_by',
        'pay_token',
        'link_sent_at',
        'recorded_method',
        'recorded_reference',
        'recorded_by',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'link_sent_at' => 'datetime',
            'recorded_at' => 'datetime',
        ];
    }

    public static function record(Model $booking, User $staff): self
    {
        return self::create([
            'bookable_type' => $booking->getMorphClass(),
            'bookable_id' => $booking->getKey(),
            'created_by' => $staff->id,
            'pay_token' => Str::random(48),
        ]);
    }

    public function bookable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function productLabel(): string
    {
        return self::PRODUCTS[$this->bookable_type]['label'] ?? class_basename($this->bookable_type);
    }

    public function seerbitCallbackRoute(): string
    {
        return self::PRODUCTS[$this->bookable_type]['seerbit_route'];
    }

    /**
     * What the customer is paying for, as label => value.
     *
     * @return array<string, string>
     */
    public function summary(): array
    {
        $booking = $this->bookable;

        if ($booking instanceof SupportYellowCard) {
            return [
                'Service' => SupportBookingService::YELLOW_CARD_SERVICES[$booking->service_type]['label'] ?? $booking->service_type,
                'Delivery address' => $booking->delivery_address,
            ];
        }

        if ($booking instanceof LoungeBooking) {
            return [
                'Lounge' => $booking->lounge_name,
                'Date' => $booking->travel_date->format('j M Y').' at '.$booking->d_time,
                'Guests' => "{$booking->noa} adult(s), {$booking->noc} child(ren), {$booking->noi} infant(s)",
            ];
        }

        return array_filter([
            'Pick-up' => $booking->pickup_location,
            'Drop-off' => $booking->dropoff_location,
            'Date' => $booking->pickup_date.' at '.$booking->pickup_time,
        ]);
    }

    /** What the customer pays. */
    public function total(): ?float
    {
        $booking = $this->bookable;

        return $booking instanceof LoungeBooking ? $booking->totalAmount() : $booking?->amount;
    }

    public function isAwaitingPayment(): bool
    {
        return $this->bookable?->payment_status === 'pending';
    }

    public function payUrl(): string
    {
        return route('staff-bookings.pay', $this->pay_token);
    }

    /** Sends the customer's confirmations, the same ones a website booking gets. */
    public function recordPayment(string $method, string $reference, User $staff): void
    {
        $this->update([
            'recorded_method' => $method,
            'recorded_reference' => $reference,
            'recorded_by' => $staff->id,
            'recorded_at' => now(),
        ]);

        app(self::PRODUCTS[$this->bookable_type]['service'])->completePayment($this->bookable, $method);
    }

    public function cancel(): void
    {
        $this->bookable->update($this->bookable instanceof LoungeBooking ? ['status' => 'Cancelled'] : ['payment_status' => 'cancelled']);
    }
}
