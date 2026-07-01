<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisaQuote extends Model
{
    protected $fillable = ['reference', 'visa_application_id', 'visa_product_id', 'visa_processing_option_id', 'product_version', 'status', 'checkout_currency', 'payable_total', 'source_totals', 'exchange_rate_snapshot', 'pricing_fingerprint', 'expires_at', 'accepted_at', 'superseded_at'];

    protected function casts(): array
    {
        return ['payable_total' => 'decimal:2', 'source_totals' => 'array', 'exchange_rate_snapshot' => 'array', 'expires_at' => 'datetime', 'accepted_at' => 'datetime', 'superseded_at' => 'datetime'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(VisaApplication::class, 'visa_application_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(VisaQuoteItem::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(VisaPayment::class);
    }
}
