<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostTicketingRequest extends Model
{
    protected $fillable = [
        'flight_booking_id',
        'admin_user_id',
        'operation_type',
        'unique_id',
        'ptr_unique_id',
        'status',
        'error_message',
        'admin_note',
        'request_payload',
        'response_payload',
        'preflight_trip_details',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'preflight_trip_details' => 'array',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(FlightBooking::class, 'flight_booking_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
