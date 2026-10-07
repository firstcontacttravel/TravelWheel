<?php

namespace App\Models;

use App\Models\Concerns\HasWorkItem;
use Illuminate\Database\Eloquent\Model;

class AirCargoModel extends Model
{
    use HasWorkItem;

    protected $table = 'aircargo';
    protected $guarded = [];
}
