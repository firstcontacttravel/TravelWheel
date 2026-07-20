<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportYellowCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_type',
        'full_name',
        'email',
        'data_page',
        'home_address',
        'phone_number',
        'delivery_address',
        'payment_option',
        'payment_reference',
        'payment_status',
        'amount',
    ];

    protected $casts = [
        'amount' => 'float',
    ];
}
