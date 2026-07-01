<?php

namespace App\Models;

use App\Models\Concerns\BumpsVisaProductVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisaFeeComponent extends Model
{
    use BumpsVisaProductVersion;

    protected $fillable = ['visa_product_id', 'visa_processing_option_id', 'processing_option_code', 'name', 'fee_type', 'traveler_type', 'calculation_basis', 'currency', 'amount', 'payee', 'pay_online', 'conditions', 'effective_from', 'effective_until', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'pay_online' => 'boolean', 'conditions' => 'array', 'effective_from' => 'datetime', 'effective_until' => 'datetime', 'is_active' => 'boolean'];
    }

    public function visaProduct(): BelongsTo
    {
        return $this->belongsTo(VisaProduct::class);
    }

    public function processingOption(): BelongsTo
    {
        return $this->belongsTo(VisaProcessingOption::class, 'visa_processing_option_id');
    }
}
