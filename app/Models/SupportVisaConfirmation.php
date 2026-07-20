<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportVisaConfirmation extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'email',
        'visa_file',
        'phone_number',
        'additional_info',
        'payment_option',
        'payment_reference',
        'payment_status',
        'amount',
    ];

    protected $casts = [
        'amount' => 'float',
    ];
}
