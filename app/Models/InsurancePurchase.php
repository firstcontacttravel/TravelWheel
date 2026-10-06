<?php

namespace App\Models;

use App\Models\Concerns\HasWorkItem;
use Illuminate\Database\Eloquent\Model;

class InsurancePurchase extends Model
{
    use HasWorkItem;

    protected $table = 'insurance_purchases';
    protected $guarded = [];
}
