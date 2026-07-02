<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingZone extends Model
{
    protected $fillable = ['zone_name'];

    public function documentPrices()
    {
        return $this->hasMany(CargoDocumentPrice::class, 'zone_id');
    }

    public function packagePrices()
    {
        return $this->hasMany(CargoPackagePrice::class, 'zone_id');
    }
}
