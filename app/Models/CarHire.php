<?php

namespace App\Models;

use App\Models\Concerns\HasWorkItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarHire extends Model
{
    use HasFactory, HasWorkItem;

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
        'duration_mins',
        'amount',
        'base_fare',
        'tear_wear_amount',
        'fuel_amount',
        'admin_fee_amount',
        'payment_option',
        'payment_reference',
        'payment_status',
        'driver_assigned',
        'fulfilment_status',
    ];

    protected $casts = [
        'pickup_date' => 'string',
        'amount' => 'float',
        'distance_km' => 'float',
        'rental_hours' => 'float',
        'duration_mins' => 'integer',
        'base_fare' => 'float',
        'tear_wear_amount' => 'float',
        'fuel_amount' => 'float',
        'admin_fee_amount' => 'float',
        'driver_assigned' => 'boolean',
    ];
}
