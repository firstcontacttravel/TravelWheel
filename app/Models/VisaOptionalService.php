<?php

namespace App\Models;

use App\Models\Concerns\BumpsVisaProductVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class VisaOptionalService extends Model
{
    use BumpsVisaProductVersion;

    protected $fillable = ['visa_product_id', 'code', 'service_type', 'name', 'description', 'customer_disclaimer', 'currency', 'amount', 'pricing_model', 'configuration', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'configuration' => 'array', 'is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(fn (VisaOptionalService $service) => $service->code ??= (string) Str::uuid());
    }

    public function visaProduct(): BelongsTo
    {
        return $this->belongsTo(VisaProduct::class);
    }
}
