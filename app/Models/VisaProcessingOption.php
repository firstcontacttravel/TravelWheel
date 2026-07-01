<?php

namespace App\Models;

use App\Models\Concerns\BumpsVisaProductVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class VisaProcessingOption extends Model
{
    use BumpsVisaProductVersion;

    protected $fillable = ['visa_product_id', 'code', 'name', 'minimum_business_days', 'maximum_business_days', 'description', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(fn (VisaProcessingOption $option) => $option->code ??= (string) Str::uuid());
    }

    public function visaProduct(): BelongsTo
    {
        return $this->belongsTo(VisaProduct::class);
    }

    public function fees(): HasMany
    {
        return $this->hasMany(VisaFeeComponent::class);
    }
}
