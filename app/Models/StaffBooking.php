<?php

namespace App\Models;

use App\Services\AirCargoBookingService;
use App\Services\GroundBookingService;
use App\Services\InsuranceBookingService;
use App\Services\LoungeBookingService;
use App\Services\ProtocolBookingService;
use App\Services\SupportBookingService;
use App\Services\VisaPaymentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
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
        ProtocolBooking::class => ['label' => 'Protocol', 'seerbit_route' => 'air.protocol.callback', 'service' => ProtocolBookingService::class],
        AirCargoModel::class => ['label' => 'Air Cargo', 'seerbit_route' => 'air.cargo.callback', 'service' => AirCargoBookingService::class],
        InsurancePurchase::class => ['label' => 'Travel Insurance', 'seerbit_route' => 'air.insurance.callback', 'service' => InsuranceBookingService::class],
        // The customer pays a visa in the website's own application, so its link goes there
        VisaApplication::class => ['label' => 'Visa', 'seerbit_route' => 'visa.payments.callback', 'service' => VisaPaymentService::class],
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

    public function bookingReference(): ?string
    {
        $booking = $this->bookable;

        return $booking instanceof VisaApplication ? $booking->reference : $booking?->payment_reference;
    }

    public function customerEmail(): ?string
    {
        $booking = $this->bookable;

        return $booking instanceof VisaApplication ? $booking->contact_email : $booking?->email;
    }

    public function isVisa(): bool
    {
        return $this->bookable instanceof VisaApplication;
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

        if ($booking instanceof VisaApplication) {
            return array_filter([
                'Visa' => $booking->product?->name,
                'Dates' => $booking->arrival_date->format('j M Y').' to '.$booking->departure_date->format('j M Y'),
                'Travellers' => "{$booking->adult_count} adult(s), {$booking->child_count} child(ren), {$booking->infant_count} infant(s)",
            ]);
        }

        if ($booking instanceof InsurancePurchase) {
            $quote = app(InsuranceBookingService::class)->quote($booking->qoute_id);

            return array_filter([
                'Cover' => (int) $booking->bookingtype_id === InsuranceBookingService::FAMILY ? 'Family, with '.(int) $booking->noc.' child(ren)' : 'Individual',
                'Dates' => $quote ? $quote->coverBegins.' to '.$quote->coverEnds : null,
            ]);
        }

        if ($booking instanceof AirCargoModel) {
            return [
                'Shipment' => $booking->shipment_type.' to '.$booking->shipping_to,
                'Shipping ID' => $booking->shipping_id,
            ];
        }

        if ($booking instanceof ProtocolBooking) {
            return [
                'Service' => ($booking->package == '2' ? 'Regular' : 'VIP').', '.$booking->airport.' '.$booking->service_type.', '.$booking->state,
                'Date' => $booking->travel_date->format('j M Y').' at '.$booking->d_time,
                'Passengers' => (string) $booking->passenger,
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

    /** Who the booking is for; a Protocol booking lists every passenger, the lead first. */
    public function customerName(): ?string
    {
        $booking = $this->bookable;

        if ($booking instanceof VisaApplication) {
            $lead = $booking->travelers->first();

            return trim(($lead?->first_name ?? '').' '.($lead?->last_name ?? '')) ?: $booking->contact_email;
        }

        return $booking instanceof ProtocolBooking ? ($booking->fullname[0] ?? null) : $booking?->full_name;
    }

    /** What the customer pays. A visa has no price until its application is finished and quoted. */
    public function total(): ?float
    {
        $booking = $this->bookable;

        if ($booking instanceof VisaApplication) {
            $quote = $booking->quotes()->whereIn('status', ['active', 'consumed'])->latest('id')->first();

            return $quote ? (float) $quote->payable_total : null;
        }

        return match (true) {
            $booking instanceof LoungeBooking, $booking instanceof ProtocolBooking => $booking->totalAmount(),
            $booking instanceof AirCargoModel => (float) $booking->total_price,
            $booking instanceof InsurancePurchase => (float) $booking->t_amount,
            default => $booking?->amount,
        };
    }

    /** The payment status in one set of words for every product: pending, paid, cancelled or failed. */
    public function paymentState(): ?string
    {
        $booking = $this->bookable;

        if ($booking instanceof VisaApplication) {
            return match (true) {
                $booking->payments()->where('status', 'paid')->exists() => 'paid',
                in_array($booking->status, ['draft', 'awaiting_payment'], true) => $booking->expires_at->isPast() ? 'expired' : 'pending',
                default => 'cancelled',
            };
        }

        $status = strtolower((string) $this->bookable?->payment_status);

        return $status === 'successful' ? 'paid' : ($status ?: null);
    }

    public function isAwaitingPayment(): bool
    {
        return $this->paymentState() === 'pending';
    }

    /** A visa is paid for once its application is finished and has a quote: before that there is no amount to receive. */
    public function canRecordPayment(): bool
    {
        return $this->isAwaitingPayment() && (! $this->isVisa() || $this->total() !== null);
    }

    public function payUrl(): string
    {
        return route('staff-bookings.pay', $this->pay_token);
    }

    /** Sends the customer's confirmations, the same ones a website booking gets. */
    public function recordPayment(string $method, string $reference, User $staff): void
    {
        // Together, so a payment the product refuses (an unfinished visa) isn't left recorded
        DB::transaction(function () use ($method, $reference, $staff): void {
            $this->update([
                'recorded_method' => $method,
                'recorded_reference' => $reference,
                'recorded_by' => $staff->id,
                'recorded_at' => now(),
            ]);

            app(self::PRODUCTS[$this->bookable_type]['service'])->completePayment($this->bookable, $method, $reference);
        });
    }

    public function cancel(): void
    {
        if ($this->bookable instanceof VisaApplication) {
            $this->cancelVisa($this->bookable);

            return;
        }

        $usesStatus = $this->bookable instanceof LoungeBooking || $this->bookable instanceof ProtocolBooking || $this->bookable instanceof InsurancePurchase;

        $this->bookable->update($usesStatus ? ['status' => 'Cancelled'] : ['payment_status' => 'cancelled']);
    }

    /**
     * Closed as "expired before submission": a cancelled visa can be reopened
     * into review, which an unpaid application must never reach.
     */
    private function cancelVisa(VisaApplication $application): void
    {
        DB::transaction(function () use ($application): void {
            $application->quotes()->where('status', 'active')->update(['status' => 'superseded', 'superseded_at' => now()]);
            $from = $application->status;
            $application->update(['status' => 'expired']);
            $application->statusHistory()->create(['from_status' => $from, 'to_status' => 'expired', 'actor_type' => 'user', 'actor_id' => auth()->id(), 'reason' => 'Cancelled by staff before payment']);
        });
    }
}
