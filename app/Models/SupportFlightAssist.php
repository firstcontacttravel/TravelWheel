<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportFlightAssist extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_type',
        'booking_source',
        'name_on_ticket',
        'airline_reference',
        'airline_category',
        'airline',
        'trip_type',
        'travel_date_oneway',
        'departure_date',
        'return_date',
        'route_from',
        'route_to',
        'preferred_time',
        'phone',
        'email',
        'additional_info',
        'payment_option',
        'amount',
        'payment_reference',
        'payment_status',
    ];

    protected $casts = [
        'travel_date_oneway' => 'date',
        'departure_date' => 'date',
        'return_date' => 'date',
        'amount' => 'float',
    ];
}
