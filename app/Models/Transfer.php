<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    use HasFactory;

    protected $table = 'transfers';

    protected $fillable = [
        'vehicle_type',
        'vehicle_name',
        'category',
        'amount',
        'distance_km',
        'duration_mins',
        'base_fare',
        'tear_wear_amount',
        'fuel_amount',
        'admin_fee_amount',
        'pickup_location',
        'dropoff_location',
        'full_name',
        'email',
        'phone_number',
        'passengers',
        'flight_number',
        'special_requests',
        'pickup_date',
        'pickup_time',
        'payment_option',
        'payment_reference',
        'payment_status',
        'driver_assigned',
    ];

    protected $casts = [
        'pickup_date' => 'string',
        'amount' => 'float',
        'distance_km' => 'float',
        'duration_mins' => 'integer',
        'base_fare' => 'float',
        'tear_wear_amount' => 'float',
        'fuel_amount' => 'float',
        'admin_fee_amount' => 'float',
        'driver_assigned' => 'boolean',
    ];
}
