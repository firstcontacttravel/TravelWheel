<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportingFact extends Model
{
    protected $fillable = [
        'source_type', 'source_id', 'product', 'sub_product', 'reference', 'customer_hash',
        'currency', 'gross_value', 'verified_collections', 'travelwheel_revenue',
        'supplier_cost', 'tax_amount', 'gross_profit', 'financially_additive',
        'payment_status', 'fulfillment_status', 'payment_method', 'payment_gateway',
        'provider', 'quantity', 'created_at_source', 'paid_at', 'service_at',
        'completed_at', 'dimensions', 'data_quality', 'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'gross_value' => 'decimal:2',
            'verified_collections' => 'decimal:2',
            'travelwheel_revenue' => 'decimal:2',
            'supplier_cost' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'gross_profit' => 'decimal:2',
            'quantity' => 'decimal:2',
            'financially_additive' => 'boolean',
            'created_at_source' => 'datetime',
            'paid_at' => 'datetime',
            'service_at' => 'datetime',
            'completed_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'dimensions' => 'array',
            'data_quality' => 'array',
        ];
    }
}
