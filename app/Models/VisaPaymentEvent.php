<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisaPaymentEvent extends Model
{
    protected $fillable = ['visa_payment_id', 'provider', 'event_hash', 'event_type', 'payload', 'processing_status', 'processing_message', 'processed_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'processed_at' => 'datetime'];
    }
}
