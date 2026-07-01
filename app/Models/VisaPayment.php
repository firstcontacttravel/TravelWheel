<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisaPayment extends Model
{
    protected $fillable = ['reference', 'visa_application_id', 'visa_quote_id', 'provider', 'status', 'expected_amount', 'expected_currency', 'verified_amount', 'verified_currency', 'idempotency_key', 'checkout_url', 'initialization_response', 'verification_response', 'failure_code', 'failure_message', 'initiated_at', 'verified_at', 'failed_at'];

    protected function casts(): array
    {
        return ['expected_amount' => 'decimal:2', 'verified_amount' => 'decimal:2', 'initialization_response' => 'array', 'verification_response' => 'array', 'initiated_at' => 'datetime', 'verified_at' => 'datetime', 'failed_at' => 'datetime'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(VisaApplication::class, 'visa_application_id');
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(VisaQuote::class, 'visa_quote_id');
    }
}
