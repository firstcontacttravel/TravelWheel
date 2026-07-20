<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarHire extends Model
{
    use HasFactory;

    protected $table = 'car_hires';

    protected $fillable = [
        'car_type',
        'category',
        'car_model',
        'full_name',
        'email',
        'phone_number',
        'passengers',
        'pickup_location',
        'dropoff_location',
        'pickup_date',
        'pickup_time',
        'distance_km',
        'rental_hours',
        'amount',
        'payment_option',
        'payment_reference',
        'payment_status',
        'driver_assigned',
    ];

    protected $casts = [
        'pickup_date' => 'string',
        'amount' => 'float',
        'distance_km' => 'float',
        'rental_hours' => 'float',
        'driver_assigned' => 'boolean',
    ];
}
