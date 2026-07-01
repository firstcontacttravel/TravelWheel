<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisaFunnelEvent extends Model
{
    protected $fillable = ['journey_id', 'visa_application_id', 'visa_product_id', 'event', 'metadata', 'idempotency_key'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }
}
