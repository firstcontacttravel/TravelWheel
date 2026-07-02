<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CargoDocumentPrice extends Model
{
    protected $table = 'cargo_document_price';
    protected $guarded = [];

    public function zone()
    {
        return $this->belongsTo(ShippingZone::class, 'zone_id');
    }
}
