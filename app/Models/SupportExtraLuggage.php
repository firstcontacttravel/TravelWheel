<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportExtraLuggage extends Model
{
    use HasFactory;

    protected $table = 'support_extra_luggage_requests';

    protected $fillable = [
        'full_name',
        'airline_category',
        'airline',
        'data_page',
        'ticket',
        'contact_number',
        'email',
        'payment_option',
        'payment_reference',
        'payment_status',
        'amount',
    ];

    protected $casts = [
        'amount' => 'float',
    ];
}
