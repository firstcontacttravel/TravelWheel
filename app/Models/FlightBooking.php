<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlightBooking extends Model
{
    protected $fillable = [
        'booking_ref',
        'unique_id',
        'fare_source_code',
        'session_id',
        'fare_type',
        'trip_type',
        'route',
        'airline',
        'cabin',
        'currency',
        'supplier_price',
        'markup_amount',
        'markup_category',
        'markup_details',
        'total_price',
        'booking_status',
        'payment_status',
        'payment_method',
        'payment_reference',
        'payment_gateway',
        'payment_flow',
        'payment_amount',
        'payment_charged_amount',
        'payment_currency',
        'payment_verified_at',
        'payment_gateway_response',
        'tkt_time_limit',
        'ticket_ordered',
        'ticket_ordered_at',
        'contact_email',
        'contact_phone',
        'adult_count',
        'child_count',
        'infant_count',
        'booking_api_response',
        'ticket_api_response',
        'itinerary_snapshot',
        'passengers_snapshot',
        'flight_snapshot',
        'extra_services_snapshot',
        'bank_transfer_reference',
        'bank_transfer_notified_at',
        'confirmation_email_sent',
        'pending_email_sent',
        'payment_receipt_sent',
    ];

    protected $casts = [
        'booking_api_response'       => 'array',
        'ticket_api_response'        => 'array',
        'itinerary_snapshot'         => 'array',
        'passengers_snapshot'        => 'array',
        'flight_snapshot'            => 'array',
        'extra_services_snapshot'    => 'array',
        'markup_details'             => 'array',
        'payment_gateway_response'   => 'array',
        'tkt_time_limit'             => 'datetime',
        'payment_verified_at'        => 'datetime',
        'ticket_ordered_at'          => 'datetime',
        'bank_transfer_notified_at'  => 'datetime',
        'ticket_ordered'             => 'boolean',
        'confirmation_email_sent'    => 'boolean',
        'pending_email_sent'         => 'boolean',
        'payment_receipt_sent'       => 'boolean',
        'total_price'                => 'decimal:2',
        'supplier_price'             => 'decimal:2',
        'markup_amount'              => 'decimal:2',
        'payment_amount'             => 'decimal:2',
        'payment_charged_amount'     => 'decimal:2',
    ];

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isWebFare(): bool
    {
        return strtolower($this->fare_type ?? '') === 'webfare';
    }

    public function isOnHold(): bool
    {
        return $this->booking_status === 'on_hold';
    }

    public function isTicketed(): bool
    {
        return $this->booking_status === 'ticketed';
    }

    public function tktTimeLimitFormatted(): string
    {
        return $this->tkt_time_limit
            ? $this->tkt_time_limit->format('D, d M Y \a\t H:i')
            : '—';
    }

    public function tktHoursRemaining(): int
    {
        if (! $this->tkt_time_limit) return 0;
        return max(0, (int) now()->diffInHours($this->tkt_time_limit, false));
    }

    public function formattedPrice(): string
    {
        $sym = match($this->currency) {
            'NGN'   => '₦',
            'USD'   => '$',
            'GBP'   => '£',
            'EUR'   => '€',
            default => $this->currency . ' ',
        };
        return $sym . number_format($this->total_price, 2);
    }

    public function totalPassengers(): int
    {
        return $this->adult_count + $this->child_count + $this->infant_count;
    }

    public function paymentVerificationRecords(): HasMany
    {
        return $this->hasMany(PaymentVerificationRecord::class);
    }

    public function ticketingRecords(): HasMany
    {
        return $this->hasMany(TicketingRecord::class);
    }

    public function travelFlexApplications(): HasMany
    {
        return $this->hasMany(TravelFlexApplication::class);
    }

    public function postTicketingRequests(): HasMany
    {
        return $this->hasMany(PostTicketingRequest::class);
    }
}
