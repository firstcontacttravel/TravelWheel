<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoungeBooking extends Model
{
    protected $table = 'lounge_service';

    protected $casts = [
        'travel_date' => 'date',
    ];

    protected $fillable = [
        'lounge_name',
        'payment_option',
        'fullname',
        'service',
        'email',
        'phone_no',
        'terminal',
        'nop',
        'noa',
        'noc',
        'noi',
        'travel_date',
        'airline',
        'd_time',
        'amount',
        'amountA',
        'amountC',
        'vat',
        'status',
        'trans_id',
        'ref_id',
    ];
}
