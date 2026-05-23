<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentVerificationRecord extends Model
{
    protected $fillable = [
        'flight_booking_id',
        'verified_by',
        'action',
        'previous_payment_status',
        'new_payment_status',
        'payment_reference',
        'amount_received',
        'currency',
        'verification_note',
        'gateway_response',
    ];

    protected $casts = [
        'amount_received' => 'decimal:2',
        'gateway_response' => 'array',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(FlightBooking::class, 'flight_booking_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
