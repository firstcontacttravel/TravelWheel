<?php

namespace App\Models;

use App\Models\Concerns\HasWorkItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportYellowCard extends Model
{
    use HasFactory, HasWorkItem;

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
        'fulfilment_status',
    ];

    protected $casts = [
        'amount' => 'float',
    ];
}
