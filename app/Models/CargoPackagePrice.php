<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CargoPackagePrice extends Model
{
    protected $table = 'cargo_package_price';
    protected $guarded = [];

    public function zone()
    {
        return $this->belongsTo(ShippingZone::class, 'zone_id');
    }
}
