<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketingRecord extends Model
{
    protected $fillable = [
        'flight_booking_id',
        'performed_by',
        'action',
        'previous_booking_status',
        'new_booking_status',
        'ticket_status',
        'airline_pnr',
        'unique_id',
        'message',
        'request_payload',
        'response_payload',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(FlightBooking::class, 'flight_booking_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
