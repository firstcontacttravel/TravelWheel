<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisaExchangeRate extends Model
{
    protected $fillable = ['source_currency', 'target_currency', 'rate', 'source', 'effective_from', 'effective_until', 'is_active'];

    protected function casts(): array
    {
        return ['rate' => 'decimal:6', 'effective_from' => 'datetime', 'effective_until' => 'datetime', 'is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (VisaExchangeRate $rate): void {
            $rate->source_currency = strtoupper($rate->source_currency);
            $rate->target_currency = strtoupper($rate->target_currency);
        });
    }
}
