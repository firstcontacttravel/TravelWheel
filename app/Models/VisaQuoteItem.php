<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisaQuoteItem extends Model
{
    protected $fillable = ['visa_quote_id', 'visa_fee_component_id', 'visa_optional_service_id', 'name', 'item_type', 'traveler_type', 'calculation_basis', 'quantity', 'source_currency', 'source_unit_amount', 'source_total', 'exchange_rate', 'checkout_currency', 'checkout_unit_amount', 'checkout_total', 'payee', 'pay_online', 'metadata', 'sort_order'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2', 'source_unit_amount' => 'decimal:2', 'source_total' => 'decimal:2', 'exchange_rate' => 'decimal:6', 'checkout_unit_amount' => 'decimal:2', 'checkout_total' => 'decimal:2', 'pay_online' => 'boolean', 'metadata' => 'array'];
    }
}
