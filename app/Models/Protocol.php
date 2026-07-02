<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Protocol extends Model
{
    protected $table = 'protocols';

    protected $fillable = [
        'location',
        'airport',
        'service',
        'price1',
        'price2',
    ];
}
