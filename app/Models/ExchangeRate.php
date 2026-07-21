<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ExchangeRate extends Model
{
    public $timestamps = false;

    protected $fillable = ['currency', 'rate'];

    protected function casts(): array
    {
        return ['rate' => 'float'];
    }

    public static function rateFor(string $currency): float
    {
        $rate = static::query()->where('currency', strtoupper($currency))->value('rate');

        if ($rate === null) {
            Log::warning("ExchangeRate::rateFor: no rate configured for currency [{$currency}], falling back to 1.0");

            return 1.0;
        }

        return (float) $rate;
    }
}
