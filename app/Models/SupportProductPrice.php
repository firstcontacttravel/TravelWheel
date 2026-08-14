<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportProductPrice extends Model
{
    protected $fillable = [
        'product',
        'label',
        'amount',
    ];

    protected function casts(): array
    {
        return ['amount' => 'float'];
    }

    public static function amountFor(string $product, float $default = 0.0): float
    {
        return (float) (static::query()->where('product', $product)->value('amount') ?? $default);
    }
}
