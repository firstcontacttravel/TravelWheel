<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lounge extends Model
{
    protected $table = 'lounges';

    protected $fillable = [
        'lounge_id',
        'brand_name',
        'email',
        'phone_no',
        'location',
        'airport',
        'terminal',
        'description',
        'facilities1',
        'facilities2',
        'facilities3',
        'facilities4',
        'facilities5',
        'given_PriceA',
        'given_PriceB',
        'given_PriceC',
        'priceA',
        'priceB',
        'priceC',
        'pics1',
        'pics2',
        'pics3',
        'pics4',
        'pics5',
    ];
}
